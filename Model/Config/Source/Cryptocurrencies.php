<?php

namespace Cryptapi\Cryptapi\Model\Config\Source;

class Cryptocurrencies implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        $list = [
            ['value' => 'btc', 'label' => 'Bitcoin'],
            ['value' => 'bch', 'label' => 'Bitcoin Cash'],
            ['value' => 'ltc', 'label' => 'Litecoin'],
            ['value' => 'eth', 'label' => 'Ethereum'],
            ['value' => 'xmr', 'label' => 'Monero'],
            ['value' => 'iota', 'label' => 'IOTA'],
        ];
        return $list;
    }
}
