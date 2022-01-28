<?php
namespace Cryptapi\Cryptapi\Block\Adminhtml\Sales\Order;

class View extends \Magento\Backend\Block\Template
{
    protected $helper;
    protected $request;
    protected $orderFactory;

    public function __construct(
        \Cryptapi\Cryptapi\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->request = $request;
        $this->orderFactory = $orderFactory;
        parent::__construct($context, $data);
    }

    public function getResponseValues()
    {
        $order_id = (int)$this->request->getParam('order_id');
        $order = $this->orderFactory->create()->load($order_id);
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance()->getCode();
        if ($method == 'cryptapi') {
            $metaData = $this->helper->getPaymentResponse($order->getQuoteId());
            return json_decode($metaData, true);
        }
        return false;
    }
}
