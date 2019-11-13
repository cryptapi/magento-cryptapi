<?php

namespace Cryptapi\Cryptapi\Block;

use Cryptapi\Cryptapi\lib\CryptAPIHelper;

class Success extends \Magento\Framework\View\Element\Template
{
    protected $helper;
    protected $payment;

    public function __construct(
        \Cryptapi\Cryptapi\Helper\Data $helper,
        \Cryptapi\Cryptapi\Model\Pay $payment,
        \Magento\Catalog\Block\Product\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->payment = $payment;
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
            $params = [
                'order_id' => $order->getId(),
                'nonce' => $metaData['cryptapi_nonce']
            ];
            $callbackUrl = $this->payment->getCallbackUrl($params);
            $selected = $cryptoCoin;
            $address = $this->payment->getConfigValue($selected);
            
            $api = new CryptAPIHelper($selected, $address, $callbackUrl, [], true);
            $addressIn = $api->getAddress();
            $this->helper->updatePaymentData($order->getQuoteId(), 'cryptapi_address', $addressIn);
        }
        
        $showCryptoCoin = $cryptoCoin;
        if ($showCryptoCoin == 'iota') {
            $showCryptoCoin = 'miota';
        }
        
        $qrValue = $cryptoValue;
        if (in_array($cryptoCoin, ['eth', 'iota'])) {
            $qrValue = CryptAPIHelper::convertMul($cryptoValue, $cryptoCoin);
        }
       
        $ajaxParams = [
            'order_id' => $order->getId()
        ];
        $ajaxUrl = $this->payment->getAjaxStatusUrl($ajaxParams);
        
        $values = [
            'crypto_value' => $cryptoValue,
            'show_crypto_coin' => strtoupper($showCryptoCoin),
            'currency_symbol' => $currencySymbol,
            'total' => $total,
            'address_in' => $addressIn,
            'qr_value' => $qrValue,
            'crypto_coin' => $cryptoCoin,
            'ajax_url' => $ajaxUrl
        ];
        
        return $values;
    }
}
