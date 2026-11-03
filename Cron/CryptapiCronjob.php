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

    public function __construct(
        ScopeConfigInterface                            $scopeConfig,
        CollectionFactory                               $orderCollectionFactory,
        Data                                            $helper,
        \Magento\Sales\Api\OrderRepositoryInterface     $orderRepository,
        \Cryptapi\Cryptapi\Model\Method\CryptapiPayment $payment,
        \Psr\Log\LoggerInterface                        $logger
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helper = $helper;
        $this->orderRepository = $orderRepository;
        $this->payment = $payment;
        $this->logger = $logger;
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

        $disable_conversion = $this->scopeConfig->getValue('payment/cryptapi/disable_conversion');

        foreach ($orders as $order) {
            $orderQuoteId = $order->getQuoteId();

            $metaData = json_decode((string)$this->helper->getPaymentResponse($order->getQuoteId()), true);

            // An order without payment data must not stop the run for every older order.
            if (empty($metaData)) {
                continue;
            }

            $history = json_decode($metaData['cryptapi_history'], true);

            $min_tx = floatval($metaData['cryptapi_min']);

            $calc = $this->payment::calcOrder($history, $metaData);

            $remaining = $calc['remaining'];
            $remaining_pending = $calc['remaining_pending'];
            $already_paid = $calc['already_paid'];

            if (!empty($metaData['cryptapi_address']) && $value_refresh !== 0 && $metaData['cryptapi_cancelled'] !== '1' && (int)$metaData['cryptapi_last_price_update'] + $value_refresh <= time() && $remaining_pending > 0) {

                $cryptapi_coin = $metaData['cryptapi_currency'];

                // Reprice only while nothing has been paid: repricing later would revalue
                // crypto the buyer already sent. A failed conversion keeps the old price.
                $crypto_total = $already_paid <= 0
                    ? CryptAPIHelper::get_conversion($order->getOrderCurrencyCode(), $cryptapi_coin, $metaData['cryptapi_total_fiat'], $disable_conversion)
                    : null;

                if (!empty($crypto_total)) {
                    $this->helper->updatePaymentData($orderQuoteId, 'cryptapi_total', $crypto_total);

                    $metaData['cryptapi_total'] = $crypto_total;
                    $calc_cron = $this->payment::calcOrder($history, $metaData);
                    $crypto_remaining_total = $calc_cron['remaining_pending'];

                    if ($crypto_remaining_total <= $min_tx) {
                        $qr_code_data_value = CryptAPIHelper::get_static_qrcode($metaData['cryptapi_address'], $cryptapi_coin, $min_tx, $this->scopeConfig->getValue('payment/cryptapi/qrcode_size'));
                    } else {
                        $qr_code_data_value = CryptAPIHelper::get_static_qrcode($metaData['cryptapi_address'], $cryptapi_coin, $crypto_remaining_total, $this->scopeConfig->getValue('payment/cryptapi/qrcode_size'));
                    }

                    if (!empty($qr_code_data_value)) {
                        $this->helper->updatePaymentData($orderQuoteId, 'cryptapi_qr_code_value', $qr_code_data_value['qr_code']);
                    }
                }

                $this->helper->updatePaymentData($orderQuoteId, 'cryptapi_last_price_update', time());
            }

            if ($order_timeout !== 0 && ((int)strtotime($order->getCreatedAt()) + $order_timeout) <= time() && empty($metaData['cryptapi_pending']) && $already_paid <= 0 && (string)$metaData['cryptapi_cancelled'] === '0') {
                $state = \Magento\Sales\Model\Order::STATE_CANCELED;
                $status = \Magento\Sales\Model\Order::STATE_CANCELED;
                $order->setState($state);
                $order->setStatus($status);
                $this->helper->updatePaymentData($orderQuoteId, 'cryptapi_cancelled', '1');
                $order->save();
            }
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
