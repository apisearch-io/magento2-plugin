<?php

namespace Apisearch\Searcher\Observer;

use Apisearch\Searcher\Helper\Data;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Apisearch\Searcher\Model\Connection;
use Magento\Setup\Exception;

class DeleteProduct implements ObserverInterface{

    private $_logger;
    /**
     * @var Data
     */
    private $_dataHelper;
    /**
     * @var Connection
     */
    private $_connection;

    public function __construct(
        Data $data,
        Connection $connection
    )
    {
        $this->_dataHelper = $data;
        $this->_connection = $connection;
        $this->_logger = $this->_dataHelper->logger();
    }

    public function execute(Observer $observer)
    {
        $realTime = $this->_dataHelper->getGeneralConfig('real_time');
        if ($realTime) {
            $product = $observer->getProduct();
            $id = $product->getId();
            $this->_logger->info("Event::Delete product");
            try {
                $stores = $this->_connection->getStoresIds();
                foreach ($stores as $store) {
                    $this->_connection->connection($store->getId());
                    $this->_connection->deleteProduct($id);
                    $this->_logger->info("Success::Delete product::ID".$id);
                }
            }catch (\Exception $e){
                $this->_logger->info("Error::Delete product::ID".$id);
                throw new \Exception($e->getMessage());
            }
        }
    }
}