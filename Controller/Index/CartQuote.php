<?php

namespace Cryptapi\Cryptapi\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;

class CartQuote implements HttpGetActionInterface
{
    protected $helper;
    protected $payment;
    protected $orderFactory;
    protected $quoteRepository;
    protected $checkoutSession;
    protected $request;
    protected $response;
    protected $scopeConfig;

    public function __construct(
        \Magento\Checkout\Model\Session                    $checkoutSession,
        \Magento\Framework\App\Request\Http                $request,
        \Magento\Framework\App\Response\Http               $response,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
        $this->response = $response;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute()
    {
        $selected = (string)$this->request->getParam('selected');

        // The coin goes into API URLs when the fee is worked out, so only enabled coins are kept.
        $enabled = json_decode((string)$this->scopeConfig->getValue('payment/cryptapi/supported_cryptocurrencies/cryptocurrencies', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), true) ?: [];

        $this->checkoutSession->setCurrency(in_array($selected, array_column($enabled, 'cryptocurrency'), true) ? $selected : null);

        $data = [
            'status' => 'done'
        ];

        $response = json_encode($data);
        return $this->response->setBody($response);
    }
}
