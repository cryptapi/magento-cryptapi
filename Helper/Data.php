<?php
namespace Cryptapi\Cryptapi\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $resource = '';
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->resource = $resource;
    }

    public function getPaymentResponse($order_id)
    {
        $connection= $this->resource->getConnection();
        $table = $this->resource->getTableName('cryptapi');
        $sql = $connection->select()
                  ->from($table, ['response'])
                  ->where('order_id = ?', (int)($order_id));
        return $connection->fetchOne($sql);
    }

    public function addPaymentResponse($order_id, $response)
    {
        $metaData = $this->getPaymentResponse($order_id);
        if (!empty($metaData)) {
            $metaData = json_decode($response, true);
            foreach ($metaData as $key => $val) {
                $this->updatePaymentData($order_id, $key, $val);
            }
        } else {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('cryptapi');
            $data = ['order_id' => $order_id, 'response' => $response];
            $connection->insert($table, $data);
        }
    }

    public function updatePaymentData($order_id, $param, $value)
    {
        $metaData = $this->getPaymentResponse($order_id);
        if (!empty($metaData)) {
            $metaData = json_decode($metaData, true);
            $metaData[$param] = $value;
            $paymentData = json_encode($metaData);

            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('cryptapi');
            $where = ['order_id = (?)' => (int)($order_id)];
            $connection->update($table, ['response' => $paymentData], $where);
        }
    }

    public function deletePaymentData($order_id, $param)
    {
        $metaData = $this->getPaymentResponse($order_id);
        if (!empty($metaData)) {
            $metaData = json_decode($metaData, true);
            if (isset($metaData[$param])) {
                unset($metaData[$param]);
            }
            $paymentData = json_encode($metaData);

            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('cryptapi');
            $where = ['order_id = (?)' => (int)($order_id)];
            $connection->update($table, ['response' => $paymentData], $where);
        }
    }
}
