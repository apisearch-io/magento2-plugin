<?php

namespace Apisearch\Searcher\Observer;

use Apisearch\Searcher\Helper\Data;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Apisearch\Searcher\Model\Connection;

class MassiveUpdate implements ObserverInterface{

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
            $this->_logger->info("Event::Massive product upgrades");
            $productIds = $observer->getProductIds();
            try {
                $this->_connection->updateItems($productIds);
                $this->_logger->info("Success::Massive product upgrades ids: ".json_encode($productIds));
            }catch (\Exception $e) {
                $this->_logger->info("Error::Massive product");
                throw new \Exception($e->getMessage());
            }
        }
    }
}