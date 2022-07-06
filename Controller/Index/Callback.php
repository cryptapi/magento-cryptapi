<?php

namespace Cryptapi\Cryptapi\Controller\Index;

use Cryptapi\Cryptapi\lib\CryptAPIHelper;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Callback implements HttpGetActionInterface
{
    protected $helper;
    protected $payment;
    protected $orderFactory;

    public function __construct(
        \Cryptapi\Cryptapi\Helper\Data                     $helper,
        \Cryptapi\Cryptapi\Model\Method\CryptapiPayment    $payment,
        \Magento\Sales\Api\OrderRepositoryInterface        $orderRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http                $request,
        \Magento\Framework\App\Response\Http               $response
    )
    {
        $this->helper = $helper;
        $this->payment = $payment;
        $this->orderRepository = $orderRepository;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->response = $response;
    }

    public function execute()
    {
        $params = $this->request->getParams();

        $data = CryptAPIHelper::process_callback($params);

        $order = $this->orderRepository->get($data['order_id']);
        $orderId = $order->getQuoteId();

        $currencySymbol = $order->getOrderCurrencyCode();

        $metaData = json_decode($this->helper->getPaymentResponse($orderId), true);

        if ($this->payment->hasBeenPaid($order) || $data['nonce'] != $metaData['cryptapi_nonce']) {
            return $this->response->setBody("*ok*");
        }

        $paid = floatval($data['value_coin']);

        $min_tx = floatval($metaData['cryptapi_min']);

        $history = json_decode($metaData['cryptapi_history'], true);

        $update_history = true;

        foreach ($history as $uuid => $item) {
            if ($uuid === $data['uuid']) {
                $update_history = false;
            }
        }

        if ($update_history) {
            $fiat_conversion = CryptAPIHelper::get_conversion($metaData['cryptapi_currency'], $currencySymbol, $paid, $this->scopeConfig->getValue('payment/cryptapi/disable_conversion', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));

            $history[$data['uuid']] = [
                'timestamp' => time(),
                'value_paid' => CryptAPIHelper::sig_fig($paid, 6),
                'value_paid_fiat' => $fiat_conversion,
                'pending' => $data['pending']
            ];
        } else {
            $history[$data['uuid']]['pending'] = $data['pending'];
        }

        $this->helper->updatePaymentData($orderId, 'cryptapi_history', json_encode($history));

        $metaData = json_decode($this->helper->getPaymentResponse($orderId), true);

        $history = json_decode($metaData['cryptapi_history'], true);

        $calc = $this->payment::calcOrder($history, $metaData);

        $remaining = $calc['remaining'];
        $remaining_pending = $calc['remaining_pending'];

        if ($remaining_pending <= 0) {
            if ($remaining <= 0) {
                $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $status = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $order->setState($state);
                $order->setStatus($status);
                $order->setTotalPaid($order->getGrandTotal());
                $order->save();
            }
            return $this->response->setBody("*ok*");
        }

        if ($remaining_pending <= $min_tx) {
            $this->helper->updatePaymentData($orderId, 'cryptapi_qr_code_value', CryptAPIHelper::get_static_qrcode($metaData['cryptapi_address'], $metaData['cryptapi_currency'], $min_tx, $this->scopeConfig->getValue('payment/cryptapi/qrcode_size', \Magento\Store\Model\ScopeInterface::SCOPE_STORE))['qr_code']);
        } else {
            $this->helper->updatePaymentData($orderId, 'cryptapi_qr_code_value', CryptAPIHelper::get_static_qrcode($metaData['cryptapi_address'], $metaData['cryptapi_currency'], $remaining_pending, $this->scopeConfig->getValue('payment/cryptapi/qrcode_size', \Magento\Store\Model\ScopeInterface::SCOPE_STORE))['qr_code']);
        }

        return $this->response->setBody("*ok*");
    }
}
