<?php

namespace Cryptapi\Cryptapi\Model\Config\Source;

class QrcodeOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            'without_ammount' => 'Default Without Amount',
            'ammount' => 'Default Amount',
            'hide_ammount' => 'Hide Amount',
            'hide_without_ammount' => 'Hide Without Amount',
        ];
    }
}
