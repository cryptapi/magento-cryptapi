<?php

namespace Cryptapi\Cryptapi\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\DataObject\Copy;

class QuoteSubmitBefore implements ObserverInterface
{
    protected $objectCopyService;
    protected $logger;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\ResourceModel\Order $orderResourceModel,
        \Cryptapi\Cryptapi\Model\Method\CryptapiPayment $payment,
        \Psr\Log\LoggerInterface $logger,
        Copy $objectCopyService
    ) {
        $this->orderResourceModel = $orderResourceModel;
        $this->orderRepository = $orderRepository;
        $this->payment = $payment;
        $this->objectCopyService = $objectCopyService;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {

        $quote = $observer->getQuote();
        $order = $observer->getOrder();
        $paymentMethod = $order->getPayment()->getMethodInstance()->getCode();

        if ($paymentMethod === 'cryptapi') {
            $order =$observer->getOrder();
            $order->setData('cryptapi_fee', (float)$quote->getData('cryptapi_fee'));
        }

    }
}
