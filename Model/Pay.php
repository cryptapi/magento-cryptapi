<?php

namespace Cryptapi\Cryptapi\Model;

use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\DataObject;
use Cryptapi\Cryptapi\lib\CryptAPIHelper;

class Pay extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'cryptapi';

    /**
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     *
     * @var Magento\Checkout\Model\Session
     */
    protected $orderSession;

    /**
     *
     * @var Cryptapi\Cryptapi\Helper\Data
     */
    protected $cryptapiHelper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    public function __construct(
        \Cryptapi\Cryptapi\Helper\Data                          $cryptapiHelper,
        \Magento\Checkout\Model\Session                         $orderSession,
        \Magento\Sales\Model\OrderFactory                       $orderFactory,
        \Magento\Customer\Model\Session                         $customerSession,
        \Magento\Framework\UrlInterface                         $urlBuilder,
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory       $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory            $customAttributeFactory,
        \Magento\Payment\Helper\Data                            $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface      $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface              $storeManager,
        \Magento\Payment\Model\Method\Logger                    $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array                                                   $data = [],
        \Magento\Directory\Helper\Data                          $directory = null
    )
    {
        $this->cryptapiHelper = $cryptapiHelper;
        $this->orderSession = $orderSession;
        $this->orderFactory = $orderFactory;
        $this->customerSession = $customerSession;
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;


        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );
    }

    public function getConfigValue($key)
    {
        $pathConfig = 'payment/' . $this->_code . "/" . $key;
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->_scopeConfig->getValue($pathConfig, $storeScope);
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_object($additionalData)) {
            $additionalData = new DataObject($additionalData ?: []);
        }

        /** @var DataObject $info */
        $info = $this->getInfoInstance();
        $info->setAdditionalInformation(
            'cryptapi_coin',
            $additionalData->getCryptapiCoin()
        );

        return $this;
    }

    public function validate()
    {
        $quote = $this->getQuote();

        $paymentInfo = $this->getInfoInstance();

        $selected = $paymentInfo->getAdditionalInformation('cryptapi_coin');

        if (empty($selected)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please select a cryptocurrency.')
            );
        }

        $nonce = $this->generateNonce();
        $info = CryptAPIHelper::get_info($selected);
        $minTx = floatval($info->minimum_transaction_coin);

        $currencyCode = $quote->getQuoteCurrencyCode();

        $total = $quote->getGrandTotal();

        $cryptoTotal = CryptAPIHelper::get_conversion(
            $selected,
            $total,
            $currencyCode,
            $this->scopeConfig->getValue('payment/cryptapi/disable_conversion', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
        );

        if ($cryptoTotal < $minTx) {
            $message = 'Payment error: Value too low, minimum is';
            $message .= ' ' . $minTx . ' ' . strtoupper($selected);
            throw new \Magento\Framework\Exception\LocalizedException(
                __($message)
            );
        }

        $paymentData = [
            'cryptapi_nonce' => $nonce,
            'cryptapi_address' => '',
            'cryptapi_total' => $cryptoTotal,
            'cryptapi_currency' => $selected,
        ];

        $paymentData = json_encode($paymentData);

        $this->cryptapiHelper->addPaymentResponse($quote->getId(), $paymentData);

        return $this;
    }

    public function getCheckout()
    {
        return $this->orderSession;
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function getOrder()
    {
        return $this->getCheckout()->getLastRealOrder();
    }

    public function getCallbackUrl($params = [])
    {
        return $this->urlBuilder->getUrl('cryptapi/index/callback', $params);
    }

    public function getAjaxStatusUrl($params = [])
    {
        return $this->urlBuilder->getUrl('cryptapi/index/status', $params);
    }

    public function hasBeenPaid($order)
    {
        if ($order->getTotalPaid() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function generateNonce($len = 32)
    {
        $data = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');

        $nonce = [];
        for ($i = 0; $i < $len; $i++) {
            $nonce[] = $data[random_int(0, count($data) - 1)];
        }

        return implode('', $nonce);
    }
}
