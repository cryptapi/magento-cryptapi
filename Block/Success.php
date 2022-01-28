<?php

namespace Cryptapi\Cryptapi\Block;

use Cryptapi\Cryptapi\lib\CryptAPIHelper;

class Success extends \Magento\Framework\View\Element\Template
{
    protected $helper;
    protected $payment;

    public function __construct(
        \Cryptapi\Cryptapi\Helper\Data                     $helper,
        \Cryptapi\Cryptapi\Model\Pay                       $payment,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Block\Product\Context             $context,
        \Psr\Log\LoggerInterface                           $logger,
        array                                              $data = []
    )
    {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->payment = $payment;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    public function getTemplateValues()
    {
        $order = $this->payment->getOrder();

        $total = $order->getGrandTotal();
        $currencySymbol = $order->getOrderCurrencyCode();

        $metaData = $this->helper->getPaymentResponse($order->getQuoteId());

        if (empty($metaData)) {
            return false;
        }

        $metaData = json_decode($metaData, true);

        $cryptoValue = $metaData['cryptapi_total'];
        $cryptoCoin = $metaData['cryptapi_currency'];

        if (isset($metaData['cryptapi_address']) && !empty($metaData['cryptapi_address'])) {
            $addressIn = $metaData['cryptapi_address'];
        } else {

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

            $api = new CryptAPIHelper($selected, $address, $callbackUrl, $params, true);
            $addressIn = $api->get_address();

            $this->helper->updatePaymentData($order->getQuoteId(), 'cryptapi_address', $addressIn);
        }

        $ajaxParams = [
            'order_id' => $order->getId()
        ];

        $ajaxUrl = $this->payment->getAjaxStatusUrl($ajaxParams);

        $qrCodeSize = $this->scopeConfig->getValue('payment/cryptapi/qrcode_size', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $branding = $this->scopeConfig->getValue('payment/cryptapi/show_branding', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $qrCode = $api->get_qrcode('', $qrCodeSize);
        $qrCodeValue = $api->get_qrcode($cryptoValue, $qrCodeSize);

        $values = [
            'crypto_value' => $cryptoValue,
            'currency_symbol' => $currencySymbol,
            'total' => $total,
            'address_in' => $addressIn,
            'crypto_coin' => $cryptoCoin,
            'ajax_url' => $ajaxUrl,
            'qrcode_size' => $qrCodeSize,
            'qrcode' => $qrCode['qr_code'],
            'qrcode_value' => $qrCodeValue['qr_code'],
            'qrcode_default' => $this->scopeConfig->getValue('payment/cryptapi/qrcode_default', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'payment_uri' => $qrCode['uri'],
            'show_branding' => $branding
        ];

        return $values;
    }
}
