<?php

namespace Cryptapi\Cryptapi\Block;

use Cryptapi\Cryptapi\lib\CryptAPIHelper;
use Magento\Framework\View\Element\Template;

class Payment extends Template
{
    protected $helper;
    protected $payment;

    public function __construct(
        \Cryptapi\Cryptapi\Helper\Data                     $helper,
        \Cryptapi\Cryptapi\Model\Method\CryptapiPayment    $payment,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Element\Template\Context   $context,
        \Magento\Sales\Api\OrderRepositoryInterface        $orderRepository,
        \Magento\Framework\App\Request\Http                $request,
        \Magento\Framework\App\ProductMetadataInterface    $productMetadata,
        \Magento\Store\Model\StoreManagerInterface         $storeManager,
        \Cryptapi\Cryptapi\Helper\Mail                     $mail,
        array                                              $data = []
    )
    {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->payment = $payment;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->productMetadata = $productMetadata;
        $this->mail = $mail;
        $this->storeManager = $storeManager;

    }

    public function getTemplateValues()
    {
        try {
            if ($this->productMetadata->getVersion() >= 2.3 && $this->productMetadata->getVersion() < 2.4) {
                $order = $this->payment->getOrder();
            } else {
                $order_id = (int)$this->request->getParam('order_id');
                $nonce = (string)$this->request->getParam('nonce');
                $order = $this->orderRepository->get($order_id);
            }

            $total = $order->getGrandTotal();
            $currencySymbol = $order->getOrderCurrencyCode();
            $metaData = $this->helper->getPaymentResponse($order->getQuoteId());

            if (empty($metaData)) {
                return false;
            }

            $qrCodeSize = $this->scopeConfig->getValue('payment/cryptapi/qrcode_size', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            $branding = $this->scopeConfig->getValue('payment/cryptapi/show_branding', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            $metaData = json_decode($metaData, true);

            if ($nonce != $metaData['cryptapi_nonce']) {
                return false;
            }

            $cryptoValue = $metaData['cryptapi_total'];
            $cryptoCoin = $metaData['cryptapi_currency'];

            if (isset($metaData['cryptapi_address']) && !empty($metaData['cryptapi_address'])) {
                $addressIn = $metaData['cryptapi_address'];
            } else {
                /*
                 * Makes request to API and generates all the payment data needed
                 */

                $selected = $cryptoCoin;

                $address = '';

                $allCryptocurrencies = json_decode($this->scopeConfig->getValue('payment/cryptapi/supported_cryptocurrencies/cryptocurrencies', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), true);

                foreach ($allCryptocurrencies as $uid => $data) {
                    if ($data['cryptocurrency'] === $selected) {
                        $address = $data['cryptocurrency_address'];
                    }
                }

                $params = [
                    'order_id' => $order->getId(),
                    'nonce' => $metaData['cryptapi_nonce'],
                ];

                $callbackUrl = $this->payment->getCallbackUrl();

                $apiKey = $this->scopeConfig->getValue('payment/cryptapi/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

                $api = new CryptAPIHelper($selected, $address, $apiKey, $callbackUrl, $params, true);
                $addressIn = $api->get_address();

                $qrCode = $api->get_qrcode('', $qrCodeSize);
                $qrCodeValue = $api->get_qrcode($cryptoValue, $qrCodeSize);

                $this->helper->updatePaymentData($order->getQuoteId(), 'cryptapi_address', $addressIn);
                $this->helper->updatePaymentData($order->getQuoteId(), 'cryptapi_qr_code_value', $qrCodeValue['qr_code']);
                $this->helper->updatePaymentData($order->getQuoteId(), 'cryptapi_qr_code', $qrCode['qr_code']);
                $this->helper->updatePaymentData($order->getQuoteId(), 'cryptapi_payment_url', $this->storeManager->getStore()->getUrl('cryptapi/index/payment/order_id/' . $order->getId() . '/nonce/' . $metaData['cryptapi_nonce']));

                $metaData = json_decode($this->helper->getPaymentResponse($order->getQuoteId()), true);
                $this->mail->sendMail($order, $metaData);
            }

            $ajaxParams = [
                'order_id' => $order->getId(),
            ];

            $ajaxUrl = $this->payment->getAjaxStatusUrl($ajaxParams);

            $metaData = $this->helper->getPaymentResponse($order->getQuoteId());
            $metaData = json_decode($metaData, true);

            return [
                'crypto_value' => floatval($cryptoValue),
                'currency_symbol' => $currencySymbol,
                'total' => $total,
                'address_in' => $addressIn,
                'crypto_coin' => $cryptoCoin,
                'ajax_url' => $ajaxUrl,
                'qrcode_size' => $qrCodeSize,
                'qrcode' => $metaData['cryptapi_qr_code'],
                'qrcode_value' => $metaData['cryptapi_qr_code_value'],
                'qrcode_default' => $this->scopeConfig->getValue('payment/cryptapi/qrcode_default', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'show_branding' => $branding,
                'qr_code_setting' => $this->scopeConfig->getValue('payment/cryptapi/qrcode_setting', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'order_timestamp' => strtotime($order->getCreatedAt()),
                'order_cancelation_timeout' => $this->scopeConfig->getValue('payment/cryptapi/order_cancelation_timeout', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'refresh_value_interval' => $this->scopeConfig->getValue('payment/cryptapi/refresh_value_interval', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'last_price_update' => $metaData['cryptapi_last_price_update'],
                'min_tx' => $metaData['cryptapi_min'],
            ];
        } catch (\Exception $exception) {
            // Empty
        }
    }
}
