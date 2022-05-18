<?php

namespace Cryptapi\Cryptapi\Model\Config\Source;

class QrcodeOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            'without_ammount' => 'Default Without Ammount',
            'ammount' => 'Default Ammount',
            'hide_ammount' => 'Hide Ammount',
            'hide_without_ammount' => 'Hide Without Ammount',
        ];
    }
}
