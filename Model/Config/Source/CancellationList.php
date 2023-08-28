<?php

namespace Cryptapi\Cryptapi\Model\Config\Source;

class CancellationList implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            '0' => 'Never',
            '900' => '15 Minutes',
            '1800' => '30 Minutes',
            '2700' => '45 Minutes',
            '3600' => '1 Hour',
            '21600' => '6 Hours',
            '43200' => '12 Hours',
            '64800' => '18 Hours',
            '86400' => '24 Hours',
        ];
    }
}
