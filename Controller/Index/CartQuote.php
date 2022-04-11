<?php

namespace Cryptapi\Cryptapi\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;

class CartQuote implements HttpGetActionInterface
{
    protected $helper;
    protected $payment;
    protected $orderFactory;
    protected $quoteRepository;

    public function __construct(
        \Magento\Checkout\Model\Session      $checkoutSession,
        \Magento\Framework\App\Request\Http  $request,
        \Magento\Framework\App\Response\Http $response,
        \Psr\Log\LoggerInterface             $logger
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
        $this->response = $response;
        $this->logger = $logger;
    }

    public function execute()
    {
        $selected = (string)$this->request->getParam('selected');

        $this->checkoutSession->setCurrency($selected);

        $data = [
            'status' => 'done'
        ];

        $response = json_encode($data);
        return $this->response->setBody($response);
    }
}
