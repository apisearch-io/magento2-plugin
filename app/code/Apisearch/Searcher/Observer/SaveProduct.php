<?php

namespace Apisearch\Searcher\Observer;

use Apisearch\Searcher\Helper\Data;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Apisearch\Searcher\Model\Connection;

class SaveProduct implements ObserverInterface{

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
            $this->_logger->info("Event::save_product_event");
            $product = $observer->getProduct();
            try {
                $this->_connection->updateItem($product);
                $this->_logger->info("Success::Saved product id ".$product->getId());
            }catch (\Exception $e){
                $this->_logger->info("Error::Saved product id ".$product->getId());
                throw new \Exception($e->getMessage());
            }
        }
    }
}