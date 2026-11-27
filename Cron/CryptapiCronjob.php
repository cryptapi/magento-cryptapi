<?php

namespace Cryptapi\Cryptapi\Cron;

use Cryptapi\Cryptapi\lib\CryptAPIHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Cryptapi\Cryptapi\Helper\Data;


class CryptapiCronjob
{
    protected $scopeConfig;
    protected $orderCollectionFactory;
    protected $helper;
    protected $orderRepository;
    protected $payment;
    protected $logger;
    protected $lockManager;

    public function __construct(
        ScopeConfigInterface                            $scopeConfig,
        CollectionFactory                               $orderCollectionFactory,
        Data                                            $helper,
        \Magento\Sales\Api\OrderRepositoryInterface     $orderRepository,
        \Cryptapi\Cryptapi\Model\Method\CryptapiPayment $payment,
        \Psr\Log\LoggerInterface                        $logger,
        \Magento\Framework\Lock\LockManagerInterface    $lockManager
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helper = $helper;
        $this->orderRepository = $orderRepository;
        $this->payment = $payment;
        $this->logger = $logger;
        $this->lockManager = $lockManager;
    }

    public function execute()
    {
        $order_timeout = (int)$this->scopeConfig->getValue('payment/cryptapi/order_cancelation_timeout');
        $value_refresh = (int)$this->scopeConfig->getValue('payment/cryptapi/refresh_value_interval');

        if ($order_timeout === 0 && $value_refresh === 0) {
            return;
        }

        $orders = $this->getOrderCollectionPaymentMethod();

        if (empty($orders)) {
            return;
        }

        foreach ($orders as $order) {
            $this->processOrder($order);
        }
    }

    /*
     * Refreshes one order's price and cancels it once it expires. The cron runs
     * this for every pending order; the payment page's status poll runs it for
     * its own order only, with a shorter timeout.
     */
    public function processOrder($order, $timeout = 20)
    {
        $order_timeout = (int)$this->scopeConfig->getValue('payment/cryptapi/order_cancelation_timeout');
        $value_refresh = (int)$this->scopeConfig->getValue('payment/cryptapi/refresh_value_interval');

        if ($value_refresh !== 0) {
            $this->refreshOrderValue($order, $value_refresh, $timeout);
        }

        if ($order_timeout === 0) {
            return;
        }

        $orderQuoteId = $order->getQuoteId();

        $metaData = json_decode((string)$this->helper->getPaymentResponse($orderQuoteId), true);

        // An order without payment data must not stop the run for every older order.
        if (empty($metaData)) {
            return;
        }

        $history = json_decode($metaData['cryptapi_history'], true);

        $calc = $this->payment::calcOrder($history, $metaData);

        $already_paid = $calc['already_paid'];

        if (((int)strtotime($order->getCreatedAt()) + $order_timeout) <= time() && empty($metaData['cryptapi_pending']) && $already_paid <= 0 && (string)$metaData['cryptapi_cancelled'] === '0') {
            $state = \Magento\Sales\Model\Order::STATE_CANCELED;
            $status = \Magento\Sales\Model\Order::STATE_CANCELED;
            $order->setState($state);
            $order->setStatus($status);
            $this->helper->updatePaymentData($orderQuoteId, 'cryptapi_cancelled', '1');
            $order->save();
        }
    }

    /*
     * One refresh per order at a time, and never over a payment. It runs in
     * three steps so a callback never waits for an API call:
     * 1. Locked: check the refresh is due and save the new time first, so polls
     *    that arrive during the API call skip this order.
     * 2. Unlocked: ask CryptAPI for the price and the QR code.
     * 3. Locked again: save them, unless a payment landed in the meantime.
     * The lock is the callback's, so a refresh and a callback never overlap.
     */
    private function refreshOrderValue($order, $value_refresh, $timeout)
    {
        $orderQuoteId = $order->getQuoteId();
        $lockName = 'cryptapi_callback_' . (int)$order->getId();

        $lock = $this->tryLock($lockName, 0);

        if ($lock === false) {
            // A callback or another refresh is on this order right now.
            return;
        }

        try {
            $metaData = json_decode((string)$this->helper->getPaymentResponse($orderQuoteId), true);

            if (empty($metaData) || empty($metaData['cryptapi_address']) || $metaData['cryptapi_cancelled'] === '1'
                || (int)$metaData['cryptapi_last_price_update'] + $value_refresh > time()) {
                return;
            }

            $this->helper->updatePaymentData($orderQuoteId, 'cryptapi_last_price_update', time());

            if (!$this->canRequote($order, $metaData)) {
                return;
            }
        } finally {
            if ($lock) {
                $this->lockManager->unlock($lockName);
            }
        }

        // A failed conversion keeps the old price; the next refresh tries again.
        $crypto_total = $this->payment->requoteOrder($order, $metaData, $timeout);

        if ($crypto_total === null) {
            return;
        }

        $history = json_decode($metaData['cryptapi_history'], true);
        $min_tx = floatval($metaData['cryptapi_min']);

        $metaData['cryptapi_total'] = $crypto_total;
        $metaData['cryptapi_total_fiat'] = $order->getGrandTotal();
        $calc_cron = $this->payment::calcOrder($history, $metaData);
        $crypto_remaining_total = $calc_cron['remaining_pending'];

        $qr_code_data_value = CryptAPIHelper::get_static_qrcode(
            $metaData['cryptapi_address'],
            $metaData['cryptapi_currency'],
            $crypto_remaining_total <= $min_tx ? $min_tx : $crypto_remaining_total,
            $this->scopeConfig->getValue('payment/cryptapi/qrcode_size'),
            $timeout
        );

        $lock = $this->tryLock($lockName, 10);

        if ($lock === false) {
            return;
        }

        try {
            $current = json_decode((string)$this->helper->getPaymentResponse($orderQuoteId), true);

            if (empty($current) || !$this->canRequote($order, $current)) {
                return;
            }

            $this->helper->updatePaymentData($orderQuoteId, 'cryptapi_total', $crypto_total);
            $this->helper->updatePaymentData($orderQuoteId, 'cryptapi_total_fiat', $order->getGrandTotal());
            $this->helper->deletePaymentData($orderQuoteId, 'cryptapi_total_unavailable');

            // The QR amount was worked out from the history read in step 1.
            if (!empty($qr_code_data_value) && $current['cryptapi_history'] === $metaData['cryptapi_history']) {
                $this->helper->updatePaymentData($orderQuoteId, 'cryptapi_qr_code_value', $qr_code_data_value['qr_code']);
            }
        } finally {
            if ($lock) {
                $this->lockManager->unlock($lockName);
            }
        }
    }

    /*
     * An order without a valid total is always priced again: there is nothing
     * to protect, and 3.2.3 and earlier could leave a null total behind. A valid
     * total is priced again only while nothing is paid, or repricing would
     * revalue crypto the buyer already sent.
     */
    private function canRequote($order, $metaData)
    {
        if ($metaData['cryptapi_cancelled'] === '1' || $this->payment->hasBeenPaid($order)) {
            return false;
        }

        if (!$this->payment::isPriced($metaData, $order)) {
            return true;
        }

        $calc = $this->payment::calcOrder(json_decode($metaData['cryptapi_history'], true), $metaData);

        return $calc['already_paid'] <= 0 && $calc['remaining_pending'] > 0;
    }

    /*
     * Returns true when locked, false when busy, and null when this connection
     * can't take another lock. Magento 2.3 allows one database lock per
     * connection, and its cron already holds one while this job runs; there the
     * refresh goes on unlocked, as before.
     */
    private function tryLock($name, $timeout)
    {
        try {
            return $this->lockManager->lock($name, $timeout);
        } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
            return null;
        }
    }

    private function getOrderCollectionPaymentMethod()
    {
        $orders = $this->orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('status',
                ['in' => ['pending']]
            );

        $orders->getSelect()
            ->join(
                ["sop" => "sales_order_payment"],
                'main_table.entity_id = sop.parent_id',
                array('method')
            )
            ->where('sop.method = ?', 'cryptapi');

        $orders->setOrder(
            'created_at',
            'desc'
        );

        return $orders;
    }
}
