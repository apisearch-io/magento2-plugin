<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Geoip
 */

namespace Apisearch\Searcher\Controller\Adminhtml\Searcher;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Apisearch\Searcher\Helper\Data;
use Apisearch\Searcher\Model\Connection;

class Reindex extends Action
{
    protected $resultJsonFactory;

    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var Connection
     */
    private $_connection;
    private $_logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Data $helper,
        Connection $connection
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->_connection = $connection;
        $this->_logger = $this->helper->logger();
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $this->_logger->info('execute reindex CONFIG...');
            $stores = $this->_connection->getStoresIds();
            foreach ($stores as $store) {
                $this->_connection->connection($store->getId());
                $this->_connection->fullUpdate($store->getId());
            }
        } catch (\Exception $e) {
            $this->_logger->err($e);
        }

        $lastCollectTime = $this->helper->getLastCollectTime();
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();

        return $result->setData(['success' => true, 'time' => $lastCollectTime]);
    }
}
