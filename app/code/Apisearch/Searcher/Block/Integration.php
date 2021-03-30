<?php

namespace Apisearch\Searcher\Block;

use Apisearch\Searcher\Helper\Data;
use Magento\Framework\View\Element\Context;

class Integration extends \Magento\Framework\View\Element\AbstractBlock
{
    private $storeConfig;

    /**
     * @param Data $storeConfig
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Data $storeConfig,
        Context $context,
        array $data = []
    ) {
        $this->storeConfig = $storeConfig;
        parent::__construct($context, $data);
    }

    /**
     * Integration script
     *
     * @return string
     */
    public function toHtml()
    {
        $indexId = $this->storeConfig->getGeneralConfig('index_id');
        $script =
            "<script type='application/javascript'>
    
    let as_snippet ='//pre.apisearch.cloud/{$indexId}.iframe.min.js?language=es';
    (function(d,t){var f=d.createElement(t),s=d.getElementsByTagName(t)[0];
    f.src=(('https:'===location.protocol)?'https:':'http:')+as_snippet;
    f.setAttribute('charset','utf-8');
    s.parentNode.insertBefore(f,s)}(document,'script'));

</script>";

        return $script;
    }
}
