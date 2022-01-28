<?php
namespace Cryptapi\Cryptapi\Controller\Index;

class Status extends \Magento\Framework\App\Action\Action
{
    protected $helper;
    protected $payment;
    protected $orderFactory;

    public function __construct(
        \Cryptapi\Cryptapi\Helper\Data $helper,
        \Cryptapi\Cryptapi\Model\Pay $payment,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->helper = $helper;
        $this->payment = $payment;
        $this->orderFactory = $orderFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $order_id = (int)$this->getRequest()->getParam('order_id');

        try {
            $order = $this->orderFactory->create()->load($order_id);
            $metaData = $this->helper->getPaymentResponse($order->getQuoteId());
            if (!empty($metaData)) {
                $metaData = json_decode($metaData, true);
            }

            $cryptapi_pending = 0;
            if (isset($metaData['cryptapi_pending'])) {
                $cryptapi_pending = $metaData['cryptapi_pending'];
            }

            $data = [
                'is_paid' => $this->payment->hasBeenPaid($order),
                'is_pending' => (int)($cryptapi_pending),
            ];

            $response = json_encode($data);
            return $this->getResponse()->setBody($response);
        } catch (\Exception $e) {
            ;
        }
        $response = json_encode(['status' => 'error', 'error' => 'Not a valid order id']);
        $response = json_encode($data);
        return $this->getResponse()->setBody($response);
    }
}
