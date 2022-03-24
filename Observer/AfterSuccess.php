<?php

namespace Cryptapi\Cryptapi\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AfterSuccess implements ObserverInterface
{

    public function __construct(
        \Cryptapi\Cryptapi\Helper\Data                  $helper,
        \Cryptapi\Cryptapi\Model\Method\CryptapiPayment $payment,
        \Magento\Framework\App\ResponseFactory          $responseFactory,
        \Magento\Framework\UrlInterface                 $url,
        \Psr\Log\LoggerInterface $logger

    )
    {
        $this->helper = $helper;
        $this->payment = $payment;
        $this->url = $url;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $order = $this->payment->getOrder();
        $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();

        if ($paymentMethod === 'cryptapi') {
            $metaData = json_decode($this->helper->getPaymentResponse($order->getQuoteId()), true);

            $params = [
                'order_id' => $order->getId(),
                'nonce' => $metaData['cryptapi_nonce']
            ];

            $redirectOrder = $this->url->getUrl('cryptapi/index/payment', $params);
            $this->responseFactory->create()->setRedirect($redirectOrder)->sendResponse();
            die();
        }
    }
}
