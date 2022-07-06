<?php
namespace Cryptapi\Cryptapi\Block\Adminhtml;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Cryptapi\Cryptapi\Block\Adminhtml\Form\Field\CryptocurrenciesSelect;

class Cryptocurrencies extends AbstractFieldArray
{
    private $cryptosField;

    protected function _prepareToRender()
    {
        $this->addColumn('cryptocurrency', [
            'label' => __('Cryptocurrency'),
            'renderer' => $this->getCryptosField(),
        ]);
        $this->addColumn('cryptocurrency_address', ['label' => __('Cryptocurrency Address'), 'class' => '']); // required-entry
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add More');
    }

    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $crypto = $row->getCryptos();
        if ($crypto !== null) {
            $options['option_' . $this->getCryptosField()->calcOptionHash($crypto)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    private function getCryptosField()
    {
        if (!$this->cryptosField) {
            $this->cryptosField = $this->getLayout()->createBlock(
                CryptocurrenciesSelect::class,
                'cryptocurrency_select',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->cryptosField;
    }
}
