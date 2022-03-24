<?php

namespace Cryptapi\Cryptapi\Model\Config\Source;

class RefreshList implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            '0' => 'Never',
            '300' => 'Every 5 Minutes',
            '600' => 'Every 10 Minutes',
            '900' => 'Every 15 Minutes',
            '1800' => 'Every 30 Minutes',
            '2700' => 'Every 45 Minutes',
            '3600' => 'Every 60 Minutes',
        ];
    }
}
