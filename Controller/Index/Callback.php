<?php

namespace Cryptapi\Cryptapi\Controller\Index;

use Cryptapi\Cryptapi\lib\CryptAPIHelper;
use Magento\Framework\Controller\ResultFactory;

class Callback extends \Magento\Framework\App\Action\Action
{
    protected $helper;
    protected $payment;
    protected $orderFactory;

    public function __construct(
        \Cryptapi\Cryptapi\Helper\Data                     $helper,
        \Cryptapi\Cryptapi\Model\Pay                       $payment,
        \Magento\Sales\Model\OrderFactory                  $orderFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Action\Context              $context
    )
    {
        $this->helper = $helper;
        $this->payment = $payment;
        $this->orderFactory = $orderFactory;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        $data = CryptAPIHelper::process_callback($params);

        $order = $this->orderFactory->create()->load($data['order_id']);

        $metaData = $this->helper->getPaymentResponse($order->getQuoteId());

        if (!empty($metaData)) {
            $metaData = json_decode($metaData, true);
        }

        if ($this->payment->hasBeenPaid($order) || $data['nonce'] != $metaData['cryptapi_nonce']) {
            return $this->getResponse()->setBody("*ok*");
        }

        $alreadyPaid = 0;

        if (isset($metaData['cryptapi_paid'])) {
            $alreadyPaid = $metaData['cryptapi_paid'];
        }

        $paid = floatval($alreadyPaid) + floatval($data['value_coin']);

        if (!$data['pending']) {
            $this->helper->updatePaymentData($order->getQuoteId(), 'cryptapi_paid', $paid);
        }

        if ($paid >= $metaData['cryptapi_total']) {
            if ($data['pending']) {
                $this->helper->updatePaymentData($order->getQuoteId(), 'cryptapi_pending', "1");
            } else {
                $this->helper->deletePaymentData($order->getQuoteId(), 'cryptapi_pending');

                $state = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $status = \Magento\Sales\Model\Order::STATE_PROCESSING;
                $order->setState($state);
                $order->setStatus($status);
                $order->setTotalPaid($order->getGrandTotal());
                $order->save();

                $this->helper->updatePaymentData($order->getQuoteId(), 'cryptapi_txid', $data['txid_in']);
            }
        }
        return $this->getResponse()->setBody("*ok*");
    }
}
