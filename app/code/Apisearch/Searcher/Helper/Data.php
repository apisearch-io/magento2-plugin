<?php

namespace Apisearch\Searcher\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{

    const XML_PATH_SEARCHER = 'searcher/';

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    public function getGeneralConfig($code, $storeId = null)
    {

        return $this->getConfigValue(self::XML_PATH_SEARCHER .'general/'. $code, $storeId);
    }

    public function getFeedConfig($code, $storeId = null)
    {

        return $this->getConfigValue(self::XML_PATH_SEARCHER .'feed_configuration/'. $code, $storeId);
    }

    public function logger()
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/apisearch.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        return $logger;
    }
}