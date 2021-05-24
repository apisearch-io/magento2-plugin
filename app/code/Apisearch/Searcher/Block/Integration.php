<?php

namespace Apisearch\Searcher\Block;

use Apisearch\Searcher\Helper\Data;
use Magento\Framework\View\Element\Context;
use Magento\Framework\Session\SessionManager;

class Integration extends \Magento\Framework\View\Element\AbstractBlock
{
    private $storeConfig;
    /**
     * @var SessionManager
     */
    private $_sessionManager;

    /**
     * @param Data $storeConfig
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Data $storeConfig,
        Context $context,
        SessionManager $sessionManager,
        array $data = []
    ) {
        $this->storeConfig = $storeConfig;
        $this->_sessionManager = $sessionManager;
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
        $sessionID = $this->_session->getSessionId();
        $script =
            "<script type='application/javascript'>
    let user_session = '{$sessionID}';
    (function(d,t){var f=d.createElement(t),s=d.getElementsByTagName(t)[0];
    f.src='https://static.apisearch.cloud/{$indexId}.iframe.min.js?';
    f.setAttribute('charset','utf-8');
    s.parentNode.insertBefore(f,s)}(document,'script'));

</script>";

        return $script;
    }
}
