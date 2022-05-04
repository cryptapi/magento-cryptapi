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
        \Psr\Log\LoggerInterface                        $logger,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\App\Response\Http            $redirect

    )
    {
        $this->helper = $helper;
        $this->payment = $payment;
        $this->url = $url;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
        $this->productMetadata = $productMetadata;
        $this->redirect = $redirect;
    }

    public function execute(Observer $observer)
    {
        $version_check = 1;

        if ($this->productMetadata->getVersion() >= 2.3 && $this->productMetadata->getVersion() < 2.4) {
            $version_check = 0;
        }

        if (empty($version_check)) {
            return false;
        }

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
            return $this->redirect->setRedirect($redirectOrder);
        }
    }
}
