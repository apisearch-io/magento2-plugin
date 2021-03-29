<?php
namespace Apisearch\Searcher\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Ranges
 */
class ConfigAttributes extends AbstractFieldArray
{
    /**
     * @var Attributes
     */
    private $attributes;

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('attribute', [
            'label' => __('Attribute'),
            'size' => '200px',
            'renderer' => $this->getAttributes()
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $tax = $row->getAttribute();
        if ($tax !== null) {
            $options['option_' . $this->getAttributes()->calcOptionHash($tax)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return Attributes
     * @throws LocalizedException
     */
    private function getAttributes()
    {
        if (!$this->attributes) {
            $this->attributes = $this->getLayout()->createBlock(
                Attributes::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->attributes;
    }
}
