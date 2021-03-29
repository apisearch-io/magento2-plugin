<?php
declare(strict_types=1);

namespace Apisearch\Searcher\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class Attributes extends Select
{

    private $_attributeFactory;

    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        Attribute $attributeFactory,
        array $data = []
    )
    {
        $this->_attributeFactory = $attributeFactory;
        parent::__construct($context, $data);
    }

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    private function getSourceOptions(): array
    {
        $attributes = $this->_attributeFactory->getCollection()->addFieldToFilter(\Magento\Eav\Model\Entity\Attribute\Set::KEY_ENTITY_TYPE_ID, 4);
        $list = array();
        foreach ($attributes as $index => $attr) {
            $list[$index]['label'] = 'Attribute: ' . $attr->getName();
            $list[$index]['value'] = $attr->getAttributeCode();
        }
        array_push($list, ['label' => '----------------','value' => '----------------']);
        array_push($list, ['label' => 'Attribute: ' . 'Available','value' => 'available']);
        array_push($list, ['label' => 'Attribute: ' . 'Reviews (Value format -> 10)','value' => 'review']);
        array_push($list, ['label' => 'Attribute: ' . 'Categories','value' => 'categories']);
        return $list;
    }
}