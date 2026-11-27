<?php

namespace Cryptapi\Cryptapi\Model\Method;

use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Framework\DataObject;
use Cryptapi\Cryptapi\lib\CryptAPIHelper;
use Magento\Payment\Model\Method\AbstractMethod;
use Cryptapi\Cryptapi\Helper\Decimal;

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
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array                                                   $data = [],
        ?\Magento\Directory\Helper\Data                          $directory = null
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
        $paymentInfo = $this->getInfoInstance();

        $selected = $paymentInfo->getAdditionalInformation('cryptapi_coin');

        // Price the quote or order being paid, not the session's cart: a guest can
        // place an order for another cart by its masked ID.
        if ($paymentInfo instanceof \Magento\Sales\Model\Order\Payment) {
            $order = $paymentInfo->getOrder();
            $quoteId = $order->getQuoteId();
            $total = $order->getGrandTotal();
            $currencyCode = $order->getOrderCurrencyCode();

            // The order is priced here, so it must not go through without a coin.
            if (empty($selected)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Please select a cryptocurrency.')
                );
            }
        } else {
            $quote = $paymentInfo->getQuote();
            $quoteId = $quote->getId();
            $total = $quote->getGrandTotal();
            $currencyCode = $quote->getQuoteCurrencyCode();
        }

        if (empty($selected)) {
            return $this;
        }

        $enabled = json_decode((string)$this->getConfigValue('supported_cryptocurrencies/cryptocurrencies'), true) ?: [];

        if (!in_array($selected, array_column($enabled, 'cryptocurrency'), true)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please select a valid cryptocurrency.')
            );
        }

        $nonce = $this->generateNonce();
        $info = CryptAPIHelper::get_info($selected);

        $cryptoTotal = CryptAPIHelper::get_conversion(
            $currencyCode,
            $selected,
            $total,
            $this->scopeConfig->getValue('payment/cryptapi/disable_conversion', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
        );

        if (empty($info) || empty($cryptoTotal)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The payment service could not be reached. Please try again.')
            );
        }

        $minTx = floatval($info->minimum_transaction_coin);

        if ($cryptoTotal < $minTx) {
            $message = 'Payment error: Value too low, minimum is';
            $message .= ' ' . $minTx . ' ' . strtoupper($selected);
            throw new \Magento\Framework\Exception\LocalizedException(
                __($message)
            );
        }

        $paymentData = [
            'cryptapi_nonce' => $nonce,
            'cryptapi_page_token' => $this->generateNonce(),
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
            'cryptapi_payment_url' => ''
        ];

        $paymentData = json_encode($paymentData);

        $this->cryptapiHelper->addPaymentResponse($quoteId, $paymentData);

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

    /*
     * A stored crypto total counts only when it is a positive amount priced from
     * this order's grand total.
     */
    public static function isPriced($meta, $order)
    {
        return CryptAPIHelper::positive_amount(isset($meta['cryptapi_total']) ? $meta['cryptapi_total'] : null) !== null
            && abs((float)(isset($meta['cryptapi_total_fiat']) ? $meta['cryptapi_total_fiat'] : 0) - (float)$order->getGrandTotal()) < 0.01;
    }

    /*
     * Prices the order's grand total in the order's own currency. Returns the
     * coin amount, or null when the call fails.
     */
    public function requoteOrder($order, $meta, $timeout = 20)
    {
        $disableConversion = $this->_scopeConfig->getValue(
            'payment/cryptapi/disable_conversion',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );

        return CryptAPIHelper::positive_amount(
            CryptAPIHelper::get_conversion($order->getOrderCurrencyCode(), $meta['cryptapi_currency'], $order->getGrandTotal(), $disableConversion, $timeout)
        );
    }

    /*
     * Token that lets the buyer open the payment page and poll its status.
     * The callback nonce must never reach the buyer, so it is kept apart.
     * Orders placed before the token existed fall back to the nonce, which
     * their buyers already hold in the emailed link.
     */
    public static function getPageToken($meta)
    {
        if (!empty($meta['cryptapi_page_token'])) {
            return (string)$meta['cryptapi_page_token'];
        }

        return (string)$meta['cryptapi_nonce'];
    }

    public static function calcOrder($history, $meta)
    {
        $already_paid = 0;
        $already_paid_fiat = 0;
        $remaining = $meta['cryptapi_total'];
        $remaining_pending = $meta['cryptapi_total'];
        $remaining_fiat = $meta['cryptapi_total_fiat'];

        if (!empty($history)) {
            foreach ($history as $uuid => $item) {
                if ((int)$item['pending'] === 0) {
                    $remaining = bcsub(CryptAPIHelper::sig_fig($remaining, 6), CryptAPIHelper::to_decimal($item['value_paid']), 8);
                }

                $remaining_pending = bcsub(CryptAPIHelper::sig_fig($remaining_pending, 6), CryptAPIHelper::to_decimal($item['value_paid']), 8);
                $remaining_fiat = bcsub(CryptAPIHelper::sig_fig($remaining_fiat, 6), CryptAPIHelper::to_decimal($item['value_paid_fiat']), 8);

                $already_paid = bcadd(CryptAPIHelper::sig_fig($already_paid, 6), CryptAPIHelper::to_decimal($item['value_paid']), 8);
                $already_paid_fiat = bcadd(CryptAPIHelper::sig_fig($already_paid_fiat, 6), CryptAPIHelper::to_decimal($item['value_paid_fiat']), 8);
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
