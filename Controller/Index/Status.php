<?php

namespace Cryptapi\Cryptapi\Controller\Index;

use Cryptapi\Cryptapi\lib\CryptAPIHelper;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Status implements HttpGetActionInterface
{
    protected $helper;
    protected $payment;
    protected $orderFactory;

    public function __construct(
        \Cryptapi\Cryptapi\Helper\Data                     $helper,
        \Cryptapi\Cryptapi\Model\Method\CryptapiPayment    $payment,
        \Magento\Sales\Api\OrderRepositoryInterface        $orderRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Cryptapi\Cryptapi\Cron\CryptapiCronjob            $cronjob,
        \Magento\Framework\App\Request\Http                $request,
        \Magento\Framework\App\Response\Http               $response,
        \Magento\Framework\Pricing\Helper\Data             $priceHelper,
        \Psr\Log\LoggerInterface                           $logger
    )
    {
        $this->helper = $helper;
        $this->payment = $payment;
        $this->orderRepository = $orderRepository;
        $this->scopeConfig = $scopeConfig;
        $this->cronjob = $cronjob;
        $this->request = $request;
        $this->response = $response;
        $this->priceHelper = $priceHelper;
        $this->logger = $logger;
    }

    public function execute()
    {
        $orderId = (int)$this->request->getParam('order_id');

        $order = $this->orderRepository->get($orderId);
        $metaData = $this->helper->getPaymentResponse($order->getQuoteId());

        if (!empty($metaData)) {
            $metaData = json_decode($metaData, true);
        }

        $showMinFee = '0';

        $history = json_decode($metaData['cryptapi_history'], true);

        $calc = $this->payment::calcOrder($history, $metaData);

        $already_paid = $calc['already_paid'];
        $already_paid_fiat = $calc['already_paid_fiat'] <= 0 ? 0 : $calc['already_paid_fiat'];

        $min_tx = floatval($metaData['cryptapi_min']);

        // $remaining = $calc['remaining'];
        $remaining_pending = $calc['remaining_pending'];
        $remaining_fiat = $calc['remaining_fiat'];

        $cryptapi_pending = '0';
        if ($remaining_pending <= 0 && !$this->payment->hasBeenPaid($order)) {
            $cryptapi_pending = '1';
        }

        $counter_calc = (int)$metaData['cryptapi_last_price_update'] + (int)$this->scopeConfig->getValue('payment/cryptapi/refresh_value_interval', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) - time();
        if (!$this->payment->hasBeenPaid($order) && $counter_calc <= 0) {
            $this->cronjob->execute();
        }

        if ($remaining_pending <= $min_tx && $remaining_pending > 0) {
            $remaining_pending = $min_tx;
            $showMinFee = '1';
        }

        $data = [
            'is_paid' => $this->payment->hasBeenPaid($order),
            'is_pending' => (int)($cryptapi_pending),
            'crypto_total' => floatval($metaData['cryptapi_total']),
            'qr_code_value' => $metaData['cryptapi_qr_code_value'],
            'cancelled' => $metaData['cryptapi_cancelled'],
            'remaining' => $remaining_pending <= 0 ? 0 : $remaining_pending,
            'fiat_remaining' => $this->priceHelper->currency(($remaining_fiat <= 0 ? 0 : $remaining_fiat), true, false),
            'coin' => strtoupper($metaData['cryptapi_currency']),
            'show_min_fee' => $showMinFee,
            'order_history' => $history,
            'already_paid' => $already_paid,
            'already_paid_fiat' => $this->priceHelper->currency($remaining_pending <= 0 ? 0 : floatval($already_paid_fiat), true, false),
            'counter' => (string)$counter_calc,
            'fiat_symbol' => $order->getOrderCurrencyCode()
        ];

        $response = json_encode($data);
        return $this->response->setBody($response);
    }
}
