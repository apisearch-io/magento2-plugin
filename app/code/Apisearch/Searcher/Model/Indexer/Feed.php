<?php
namespace Apisearch\Searcher\Model\Indexer;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Apisearch\Searcher\Model\Connection;

class Feed implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @var CollectionFactory
     */
    private $_collectionFactory;
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;
    private $_logger;
    /**
     * @var Connection
     */
    private $_connection;

    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        Connection $connection
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_storeManager = $storeManager;
        $this->_connection = $connection;
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/apisearch.log');
        $this->_logger = new \Zend\Log\Logger();
        $this->_logger->addWriter($writer);
    }

    /*
     * Used by mview, allows process indexer in the "Update on schedule" mode
     */
    public function execute($ids)
    {
        $this->_logger->info('execute reindex EXECUTE... IDS:'.$ids);
    }

    /*
     * Will take all of the data and reindex
     * Will run when reindex via command line
     */
    public function executeFull()
    {
        $this->_logger->info('execute reindex EXECUTE__FULL...');
        $this->_connection->fullUpdate();
    }


    /*
     * Works with a set of entity changed (may be massaction)
     */
    public function executeList(array $ids){
        $this->_logger->info('execute reindex EXECUTE__LIST...');
    }


    /*
     * Works in runtime for a single entity using plugins
     */
    public function executeRow($id){
        $this->_logger->info('execute reindex EXECUTE__ROW...');
    }
}