<?php

namespace Cryptapi\Cryptapi\Controller\Index;

use Cryptapi\Cryptapi\lib\CryptAPIHelper;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class Callback implements HttpGetActionInterface, HttpPostActionInterface, CsrfAwareActionInterface
{
    protected $helper;
    protected $payment;
    protected $orderFactory;
    protected $cache;
    protected $storeManager;
    protected $lockManager;
    protected $orderRepository;
    protected $scopeConfig;
    protected $request;
    protected $response;

    public function __construct(
        \Cryptapi\Cryptapi\Helper\Data                     $helper,
        \Cryptapi\Cryptapi\Model\Method\CryptapiPayment    $payment,
        \Magento\Sales\Api\OrderRepositoryInterface        $orderRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http                $request,
        \Magento\Framework\App\Response\Http               $response,
        \Magento\Framework\App\CacheInterface              $cache,
        \Magento\Store\Model\StoreManagerInterface         $storeManager,
        \Magento\Framework\Lock\LockManagerInterface       $lockManager
    )
    {
        $this->helper = $helper;
        $this->payment = $payment;
        $this->orderRepository = $orderRepository;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->response = $response;
        $this->cache = $cache;
        $this->storeManager = $storeManager;
        $this->lockManager = $lockManager;
    }

    public function execute()
    {
        $params = $this->getSignedParams();

        if ($params === null) {
            return $this->response->setHttpResponseCode(401)->setBody('Invalid signature');
        }

        $data = CryptAPIHelper::process_callback($params);

        // Two callbacks for one order must not interleave, or one payment is lost.
        $lockName = 'cryptapi_callback_' . (int)$data['order_id'];

        if (!$this->lockManager->lock($lockName, 30)) {
            return $this->response->setHttpResponseCode(503)->setBody('Busy');
        }

        try {
            return $this->processCallback($data);
        } finally {
            $this->lockManager->unlock($lockName);
        }
    }

    // Callbacks come from CryptAPI without a form key; the signature check guards them.
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    private function processCallback($data)
    {
        $order = $this->orderRepository->get($data['order_id']);
        $orderId = $order->getQuoteId();

        $currencySymbol = $order->getOrderCurrencyCode();

        $metaData = json_decode((string)$this->helper->getPaymentResponse($orderId), true);

        if (empty($metaData)) {
            return $this->response->setBody("*ok*");
        }

        // Anyone can ask CryptAPI for an address that calls this URL, so a signed
        // callback only counts if it is for the address we created for this order.
        if ($data['coin'] !== $metaData['cryptapi_currency'] || empty($metaData['cryptapi_address']) || strcasecmp((string)$data['address_in'], $metaData['cryptapi_address']) !== 0) {
                return $this->response->setBody("*ok*");
        }

        if ($this->payment->hasBeenPaid($order) || !hash_equals((string)$metaData['cryptapi_nonce'], (string)$data['nonce'])) {
            return $this->response->setBody("*ok*");
        }

        // Pending callbacks may come without an amount; the confirmed one carries it.
        if ($data['uuid'] === null || $data['value_coin'] === null) {
            return $this->response->setBody("*ok*");
        }

        $paid = CryptAPIHelper::to_decimal($data['value_coin']);

        $min_tx = floatval($metaData['cryptapi_min']);

        $history = json_decode($metaData['cryptapi_history'], true) ?: [];

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
        } elseif ((int)$history[$data['uuid']]['pending'] !== 0) {
            // A confirmed payment never goes back to pending, whatever a late or replayed callback says.
            $history[$data['uuid']]['pending'] = $data['pending'];
        }

        $this->helper->updatePaymentData($orderId, 'cryptapi_history', json_encode($history));

        $metaData = json_decode($this->helper->getPaymentResponse($orderId), true);

        $history = json_decode($metaData['cryptapi_history'], true);

        $calc = $this->payment::calcOrder($history, $metaData);

        $remaining = $calc['remaining'];
        $remaining_pending = $calc['remaining_pending'];

        if ($remaining_pending <= 0) {
            // Only trust a crypto total that loaded and was priced from this order's total.
            // A cancelled order stays cancelled; the payment is in its history for the merchant.
            $priced = (float)$metaData['cryptapi_total'] > 0
                && abs((float)$metaData['cryptapi_total_fiat'] - (float)$order->getGrandTotal()) < 0.01;

            if ($remaining <= 0 && $priced && !$order->isCanceled()) {
                $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $status = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $order->setState($state);
                $order->setStatus($status);
                $order->setTotalPaid($order->getGrandTotal());
                $order->save();
            }
            return $this->response->setBody("*ok*");
        }

        $qr_code = CryptAPIHelper::get_static_qrcode($metaData['cryptapi_address'], $metaData['cryptapi_currency'], $remaining_pending <= $min_tx ? $min_tx : $remaining_pending, $this->scopeConfig->getValue('payment/cryptapi/qrcode_size', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));

        if (!empty($qr_code)) {
            $this->helper->updatePaymentData($orderId, 'cryptapi_qr_code_value', $qr_code['qr_code']);
        }

        return $this->response->setBody("*ok*");
    }

    /*
     * CryptAPI signs every callback with RSA-SHA256 and sends it in the x-ca-signature
     * header. Returns the callback's parameters, or null when the signature fails.
     */
    private function getSignedParams()
    {
        $signature = base64_decode((string)$this->request->getHeader('x-ca-signature'), true);

        if (empty($signature)) {
            return null;
        }

        $pubkey = $this->getPubkey();

        if (empty($pubkey)) {
            return null;
        }

        // Addresses created since this version call back by POST, signed over the raw
        // body, which proxies such as Fastly leave alone. The order ID and nonce stay
        // in the query string; signed body values win over it.
        if ($this->request->isPost()) {
            $body = (string)$this->request->getContent();

            if (openssl_verify($body, $signature, $pubkey, OPENSSL_ALGO_SHA256) !== 1) {
                return null;
            }

            parse_str($body, $post);

            return array_merge($this->request->getQuery()->toArray(), $post);
        }

        // Older addresses call back by GET, signed over the full URL. The URL is rebuilt
        // from the stores' own base URLs, never from request headers the caller controls.
        $requestUri = (string)$this->request->getServer('REQUEST_URI');

        foreach ($this->storeManager->getStores() as $store) {
            foreach ([false, true] as $secure) {
                $url = parse_url($store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, $secure));
                $origin = $url['scheme'] . '://' . $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '');

                if (openssl_verify($origin . $requestUri, $signature, $pubkey, OPENSSL_ALGO_SHA256) === 1) {
                    return $this->request->getQuery()->toArray();
                }
            }
        }

        return null;
    }

    private function getPubkey()
    {
        $pubkey = $this->cache->load('cryptapi_pubkey');

        if (empty($pubkey)) {
            $pubkey = CryptAPIHelper::get_pubkey();

            if (!empty($pubkey)) {
                $this->cache->save($pubkey, 'cryptapi_pubkey', [], 3600);
            }
        }

        return $pubkey;
    }
}
