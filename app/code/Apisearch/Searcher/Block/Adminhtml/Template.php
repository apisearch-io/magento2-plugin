<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Geoip
 */

namespace Apisearch\Searcher\Block\Adminhtml;

/**
 * Class Template
 */
class Template extends \Magento\Backend\Block\Template
{
    /**
     * @var \Apisearch\Searcher\Helper\Data
     */
    private $_dataHelper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Apisearch\Searcher\Helper\Data $data
    ) {
        parent::__construct($context);

        $this->_dataHelper = $data;
    }
}
