<?php

namespace Apisearch\Searcher\Block;

use Apisearch\Searcher\Helper\Data;
use Magento\Framework\View\Element\Context;
use Magento\Framework\Session\SessionManager;
use Magento\Customer\Model\Session;
use Magento\Checkout\Model\Cart;

class Integration extends \Magento\Framework\View\Element\AbstractBlock
{
    private $storeConfig;
    /**
     * @var Session
     */
    private $_customerSession;
    /**
     * @var Cart
     */
    private $_cart;

    /**
     * @param Data $storeConfig
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Data $storeConfig,
        Context $context,
        Session $customerSession,
        Cart $cart,
        array $data = []
    ) {
        $this->storeConfig = $storeConfig;
        $this->_customerSession = $customerSession;
        $this->_cart = $cart;
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

        if ($this->_customerSession->isLoggedIn()) {
            $sessionID = hash('md5',$this->_customerSession->getCustomer()->getId());
        } else {
            $quoteId =$this->_cart->getQuote()->getId();
            if ($quoteId) {
                $sessionID = hash('md5',$quoteId);
            } else {
                $sessionID = hash('md5',$this->_session->getSessionId());
            }
        }

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
