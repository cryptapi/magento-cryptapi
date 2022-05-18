<?php

namespace Cryptapi\Cryptapi\Model\Config\Source;

class SchemeList implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            'light' => 'Light',
            'dark' => 'Dark',
            'auto' => 'Auto',
        ];
    }
}
