<?php

namespace Cryptapi\Cryptapi\Model\Total;

use Cryptapi\Cryptapi\lib\CryptAPIHelper;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Fee extends AbstractTotal
{
    /**
     * Collect grand total address amount
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    protected $quoteValidator = null;

    public function __construct(
        \Magento\Checkout\Model\Session          $checkoutSession,
        ScopeConfigInterface                     $scopeConfig,
        \Magento\Quote\Model\Quote\Address\Total $orderTotals,
        \Psr\Log\LoggerInterface                 $logger
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->totals = $orderTotals;
        $this->logger = $logger;
    }

    public function collect(
        Quote                       $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total                       $total
    )
    {
        parent::collect($quote, $shippingAssignment, $total);

        if (!count($shippingAssignment->getItems())) {
            return $this;
        }

        $fee = $this->calculateFee($quote);

        $total->setTotalAmount('fee', $fee);
        $total->setBaseTotalAmount('fee', $fee);
        $total->setFee($fee);
        $total->setBaseFee($fee);
        $total->setGrandTotal($total->getGrandTotal());
        $total->setBaseGrandTotal($total->getBaseGrandTotal());


        $quote->setFee($fee);

        return $this;
    }

    protected function clearValues(Total $total)
    {
        $total->setTotalAmount('subtotal', 0);
        $total->setBaseTotalAmount('subtotal', 0);
        $total->setTotalAmount('tax', 0);
        $total->setBaseTotalAmount('tax', 0);
        $total->setTotalAmount('discount_tax_compensation', 0);
        $total->setBaseTotalAmount('discount_tax_compensation', 0);
        $total->setTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setBaseTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setSubtotalInclTax(0);
        $total->setBaseSubtotalInclTax(0);
    }

    public function fetch(Quote $quote, Total $total)
    {
        return [
            'code' => 'fee',
            'title' => __('Service Fee'),
            'value' => $this->calculateFee($quote),
        ];
    }

    private function calculateFee(Quote $quote)
    {

        try {
            $paymentMethod = $quote->getPayment()->getMethodInstance()->getCode();

            if ($paymentMethod === 'cryptapi') {
                $conv = 0;
                $totalPrice = 0;

                $feePercentage = $this->scopeConfig->getValue('payment/cryptapi/fee_order_percentage', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                $estimateBlockchain = $this->scopeConfig->getValue('payment/cryptapi/add_blockchain_fee', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                $coin = $this->checkoutSession->getCurrency();

                if ($feePercentage !== 'none') {
                    $totalPrice = $quote->getGrandTotal() * $feePercentage;
                }

                if (!empty($coin) && $estimateBlockchain) {
                    $conv = CryptAPIHelper::get_estimate($coin)->{$quote->getQuoteCurrencyCode()};
                }

                return $totalPrice + $conv;
            }
        } catch (\Exception $ex) {
            return 0;
        }

        return 0;
    }
}
