<?php

namespace Cryptapi\Cryptapi\Model\Method;


use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\DataObject;
use Cryptapi\Cryptapi\lib\CryptAPIHelper;
use Magento\Payment\Model\Method\AbstractMethod;


class CryptapiPayment extends AbstractMethod
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
            return $this;
        }

        $nonce = $this->generateNonce();
        $info = CryptAPIHelper::get_info($selected);
        $minTx = floatval($info->minimum_transaction_coin);

        $currencyCode = $quote->getQuoteCurrencyCode();

        $total = $quote->getGrandTotal();

        $cryptoTotal = CryptAPIHelper::get_conversion(
            $currencyCode,
            $selected,
            $total,
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
            'cryptapi_total_fiat' => $total,
            'cryptapi_currency' => $selected,
            'cryptapi_history' => json_encode([]),
            'cryptapi_cancelled' => '0',
            'cryptapi_last_price_update' => time(),
            'cryptapi_min' => $minTx,
            'cryptapi_qr_code_value' => '',
            'cryptapi_qr_code' => '',
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

    public static function calcOrder($history, $total, $total_fiat)
    {
        $already_paid = 0;
        $already_paid_fiat = 0;
        $remaining = $total;
        $remaining_pending = $total;
        $remaining_fiat = $total_fiat;

        if (!empty($history)) {
            foreach ($history as $uuid => $item) {
                if ((int)$item['pending'] === 0) {
                    $remaining = bcsub($remaining, $item['value_paid'], 18);
                }

                $remaining_pending = bcsub($remaining_pending, $item['value_paid'], 18);
                $remaining_fiat = bcsub($remaining_fiat, $item['value_paid_fiat'], 18);

                $already_paid = bcadd($already_paid, $item['value_paid'], 18);
                $already_paid_fiat = bcadd($already_paid_fiat, $item['value_paid_fiat'], 18);
            }
        }

        return [
            'already_paid' => floatval($already_paid),
            'already_paid_fiat' => floatval($already_paid_fiat),
            'remaining' => floatval($remaining),
            'remaining_pending' => floatval($remaining_pending),
            'remaining_fiat' => floatval($remaining_fiat)
        ];
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
