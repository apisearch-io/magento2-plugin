<?php

namespace Apisearch\Searcher\Model;

use Apisearch\Searcher\Helper\Data;
use Apisearch\Searcher\Helper\ApisearchClient;
use Magento\Catalog\Model\Layer\Category\FilterableAttributeList;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product;
use Magento\Review\Model\ReviewFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Swatches\Helper\Data as SwatchesHelper;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryFactory;

class Connection extends ApisearchClient {

    private $_logger;
    protected $host = 'https://pre.apisearch.io';
    protected $version = 'v1';
    /**
     * @var Data
     */
    private $_dataHelper;
    /**
     * @var FilterableAttributeList
     */
    private $_filterableProducts;
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;
    /**
     * @var CollectionFactory
     */
    private $_collectionFactory;
    /**
     * @var ReviewFactory
     */
    private $_reviewFactory;
    /**
     * @var SwatchesHelper
     */
    private $_swatchHelper;
    /**
     * @var StockItemRepository
     */
    private $_stockItemRepository;
    /**
     * @var ProductRepository
     */
    private $_productRepository;
    /**
     * @var CategoryFactory
     */
    private $_categoryFactory;

    public function __construct(
        Data $data,
        FilterableAttributeList $filterableProdcuts,
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        ReviewFactory $reviewFactory,
        SwatchesHelper $swatchHelper,
        StockItemRepository $stockItemRepository,
        ProductRepository $productRepository,
        CategoryFactory $categoryFactory
    )
    {
        $this->_dataHelper = $data;
        $this->_filterableProducts = $filterableProdcuts;
        $this->_storeManager = $storeManager;
        $this->_logger = $this->_dataHelper->logger();
        $this->_collectionFactory = $collectionFactory;
        $this->_reviewFactory = $reviewFactory;
        $this->_swatchHelper = $swatchHelper;
        $this->_stockItemRepository = $stockItemRepository;
        $this->_productRepository = $productRepository;
        $this->_categoryFactory = $categoryFactory;
        $this->connection();
        parent::__construct($this->host,$this->version);
    }

    public function connection()
    {
        $appUUID = $this->_dataHelper->getGeneralConfig('appUUID');
        $indexUUID = $this->_dataHelper->getGeneralConfig('index_id');
        $tokenUUID = $this->_dataHelper->getGeneralConfig('tokenUUID');
        $this->setCredentials($appUUID,$indexUUID,$tokenUUID);
    }

    public function getAtributeSwatchHashcode($optionid) {
        $hashcodeData = $this->_swatchHelper->getSwatchesByOptionsId([$optionid]);
        if (is_array($hashcodeData)){
            if (isset($hashcodeData[$optionid]['value'])) {
                return $hashcodeData[$optionid]['value'];
            }else {
                return null;
            }
        }else {
            return null;
        }

    }

    public function isSwatchAttr($attr)
    {
        return $this->_swatchHelper->isSwatchAttribute($attr);
    }

    public function getCategoriesData($ids)
    {
        $categories = $this->_categoryFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('entity_id', $ids);
        foreach ($categories as $category){
            $name = $category->getName();
            $url = $category->getUrl();
        }
        return $categories;
    }

    public function converData()
    {
        $metadata['metadata'] = json_decode($this->_dataHelper->getFeedConfig('listting_attributes'));
        $indexed_metadata['indexed_metadata'] = json_decode($this->_dataHelper->getFeedConfig('filterable_attributes'));
        $searchable_metadata['searchable_metadata'] = json_decode($this->_dataHelper->getFeedConfig('searchable_attributes'));
        $exact_matching_metadata['exact_matching_metadata'] = json_decode($this->_dataHelper->getFeedConfig('exact_matching_metadata'));
        $data = array_merge($metadata,$indexed_metadata,$searchable_metadata,$exact_matching_metadata);

        $arrayAttr2 = array();
        foreach ($data as $i => $val) {
            foreach ($val as $f => $v) {
                $arrayAttr2[$i][$v->attribute] = null;
            }
        }
        return $arrayAttr2;
    }

    public function metadata(Product $product)
    {
        $array = $this->converData();
        $data = array('metadata' => array(),'indexed_metadata' => array(),'searchable_metadata' => array(),'exact_matching_metadata' => array());
        foreach ($array as $index => $value) {
            foreach ($value as $code => $val) {
                $attr = $product->getResource()->getAttribute($code);
                if ($index == 'exact_matching_metadata') {
                    $data['exact_matching_metadata'][] = $attr->getFrontend()->getValue($product);
                } else {
                    if ($code == 'available') {
                        $isInStock = $product->isAvailable();
                        $data[$index][$code] = $isInStock ? 1 : 0;
                        continue;
                    }
                    if ($code == 'color'){
                        $data[$index][$code] = $this->getAtributeSwatchHashcode($product->getData($attr->getName()));
                        continue;
                    }
                    if ($code == 'review'){
                        if ($this->getReviewCollection($product) != null) {
                            $data[$index][$code] = $this->getReviewCollection($product);
                            continue;
                        }
                    }
                    if ($code == 'categories'){
                        $categoriesIds = $product->getCategoryIds();
                        $data[$index][$code] = $this->getCategoriesData($categoriesIds);
                        continue;
                    }
                    if (!is_bool($attr)){
                        $attrconf = $attr->getFrontend()->getValue($product);
                        if ($attrconf != null){
                            if ($code == 'url_key') {
                                $data[$index]['url'] = $product->getProductUrl();
                            }else if ($code == 'image') {
                                $data['metadata'][$attr->getName()] = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
                            }else if ($code == 'price') {
                                $price = floatval($product->getPrice());
                                $finalPrice = floatval($product->getFinalPrice());
                                $discount = $price != $finalPrice ? (($price-$finalPrice)/$price)*100 : null;
                                $data[$index]['price'] = $price;
                                $data[$index]['final_price'] = $finalPrice;
                                $data[$index]['discount'] = intval($discount);
                            } else if ($attrconf == 'Yes' || $attrconf == 'No'){
                                $data[$index][$attr->getName()] = $attrconf == 'Yes' ? 1 : 0;
                            } else {
                                $data[$index][$attr->getName()] = is_object($attrconf) ? $attrconf->getText() : $attrconf;
                            }
                        } else {
                            $this->_logger->info('Error al recuperar attributo: '.$code.' para el producto con sku: '.$product->getSku(). '--indexer_metadata');
                        }
                    }
                }
            }
        }
        return $data;
    }

    public function metadataConfigurable(Product $product,$children)
    {
        $array = $this->converData();
        $data = array('metadata' => array(),'indexed_metadata' => array(),'searchable_metadata' => array(),'exact_matching_metadata' => array());
        foreach ($array as $index => $value) {
            foreach ($value as $code => $val) {
                $attr = $product->getResource()->getAttribute($code);
                if ($index == 'exact_matching_metadata') {
                    $data['exact_matching_metadata'][] = $attr->getFrontend()->getValue($product);
                } else if ($index != 'indexed_metadata'){
                    if (!is_bool($attr)){
                        $attrconf = $attr->getFrontend()->getValue($product);
                        if ($attrconf != null){
                            if ($code == 'url_key') {
                                $data[$index]['url'] = $product->getProductUrl();
                            } else if ($code == 'image') {
                                $data['metadata'][$attr->getName()] = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
                            } else {
                                $data[$index][$attr->getName()] = is_object($attrconf) ? $attrconf->getText() : $attrconf;
                            }
                        } else {
                            $this->_logger->info('Error al recuperar attributo: '.$code.' para el producto con sku: '.$product->getSku());
                        }
                    } else {
                        if($code == 'review') {
                            if ($this->getReviewCollection($product) != null) {
                                $data[$index][$code] = $this->getReviewCollection($product);
                                continue;
                            }
                        }
                    }
                } else {
                    foreach ($children as $v => $child) {
                        if ($v == 0 && $code == 'price') {
                            $price = floatval($child->getPrice());
                            $finalPrice = floatval($child->getFinalPrice());
                            $discount = $price != $finalPrice ? (($price-$finalPrice)/$price)*100 : null;
                            $data[$index]['price'] = $price;
                            $data[$index]['final_price'] = $finalPrice;
                            $data[$index]['discount'] = intval($discount);
                            break;
                        } else if ($code == 'available') {
                            $isInStock = $product->isAvailable();
                            $data[$index][$code] = $isInStock ? 1 : 0;
                            break;
                        } else {
                            $filterableValue = $attr->getFrontend()->getValue($child);
                            if ($filterableValue) {
                                if ($code == 'color'){
                                    $data[$index][$attr->getName()][$v] = $this->getAtributeSwatchHashcode($child->getData($attr->getName()));
                                    continue;
                                }
                                if ($filterableValue == 'Yes' || $filterableValue == 'No'){
                                    $data[$index][$attr->getName()] = $filterableValue == 'Yes' ? 1 : 0;
                                    break;
                                } else {
                                    $data[$index][$attr->getName()][$v] = is_object($filterableValue) ? $filterableValue->getText() : $filterableValue;
                                }
                            } else {
                                $filterableValue = $attr->getFrontend()->getValue($product);
                                if ($filterableValue) {
                                    $data[$index][$attr->getName()][$v] = is_object($filterableValue) ? $filterableValue->getText() : $filterableValue;
                                    break;
                                }else {
                                    $this->_logger->info('Error al recuperar attributo: '.$code.' para el producto con sku: '.$product->getSku(). '--indexer_metadata');
                                }
                            }
                        }
                    }
                    if ($code != 'price' && $attr != null && isset($data[$index][$attr->getName()]) && !is_int($data[$index][$attr->getName()])) {// Dar un vuelta
                        $data[$index][$attr->getName()] = array_values(array_unique($data[$index][$attr->getName()]));
                    }
                }
            }
        }
        return $data;
    }

    public function updateItem(Product $product, $event = 'event')
    {
        if ($product->getTypeId() == 'configurable') {
            if ($product->getVisibility() != Visibility::VISIBILITY_NOT_VISIBLE && $product->getStatus() == Status::STATUS_ENABLED){
                $childen = $product->getTypeInstance()->getUsedProducts($product);
                $data = $this->metadataConfigurable($product,$childen);
                $data['indexed_metadata']['configurable'] = 1;
                $this->productDataPush($product, $data, $event);
            } else {
                $this->deleteProduct($product->getId());
            }
        } else if ($product->getTypeId() == 'simple' || $product->getTypeId() == 'virtual' || $product->getTypeId() == 'download') {
            if ($product->getVisibility() != Visibility::VISIBILITY_NOT_VISIBLE && $product->getStatus() == Status::STATUS_ENABLED){
                $this->_logger->info($product->getSku().'-- Product type: '.$product->getTypeId());
                $data = $this->metadata($product);
                $data['indexed_metadata']['configurable'] = 0;
                $this->productDataPush($product, $data, $event);
            } else if ($product->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE && $product->getStatus() == Status::STATUS_ENABLED){
                //tenemos que actualizar el padre si actualizamos algun hijo suyo? realmente es necesario?
            } else {
                $this->deleteProduct($product->getId());
            }
        }else if ($product->getTypeId() == 'grouped') { //precio desde ¿?¿? Obetenemos el menor
            if ($product->getVisibility() != Visibility::VISIBILITY_NOT_VISIBLE && $product->getStatus() == Status::STATUS_ENABLED){
                $associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);
                $data = $this->metadataConfigurable($product,$associatedProducts);
                $data['indexed_metadata']['configurable'] = 1;
                $this->productDataPush($product, $data, $event);
            } else {
                $this->deleteProduct($product->getId());
            }
        } else {
            // Types of non magento products
            if ($product->getVisibility() != Visibility::VISIBILITY_NOT_VISIBLE && $product->getStatus() == Status::STATUS_ENABLED){
                $data = $this->metadata($product);
                $data['indexed_metadata']['configurable'] = 0;
                $this->productDataPush($product, $data, $event);
            } else {
                $this->deleteProduct($product->getId());
            }
        }
    }

    public function productDataPush (Product $product, $data, $event){
        $id = $product->getId();
        $name = $product->getName();
        // Solo Marvimundo
        //$attr = $product->getResource()->getAttribute('marca')->getFrontend()->getValue($product);
        //$marca = is_object($attr) ? $attr->getText() : $attr;
        //$attr = $product->getResource()->getAttribute('submarca')->getFrontend()->getValue($product);
        //$subMarca = is_object($attr) ? $attr->getText() : $attr;
        $productData = array(
            "uuid" => [
                "type"=> "product",
                "id"=> $id,
            ],
            "metadata" => $data['metadata'],
            "indexed_metadata" => $data['indexed_metadata'],
            "searchable_metadata" => $data['searchable_metadata'],
            "exact_matching_metadata" => $data['exact_matching_metadata'],
            "suggest" => [
                $name,
                //$marca,
                //$subMarca
            ]
        );
        $this->putItem($productData);
        if ($event == 'indexer') {
            $this->flush();
        } else {
            $this->flush(1,false);
        }
    }

    public function getReviewCollection($product){
        $this->_reviewFactory->create()->getEntitySummary($product, $this->_storeManager->getStore()->getId());
        $ratingSummary = $product->getRatingSummary()->getRatingSummary();
        if ($ratingSummary) {
            return $ratingSummary/10;
        }
        return null;
    }

    public function UpdateItems(array $ids)
    {
        foreach ($ids as $id) {
            $item = $this->getProduct($id);
            $this->updateItem($item);
        }
    }

    public function fullUpdate()
    {
        $collection = $this->_collectionFactory->create();
        $collection->addAttributeToSelect('*')->addFinalPrice();
        $collection->addAttributeToFilter('status', ['in' => Status::STATUS_ENABLED]);
        foreach ($collection as $item) {
            $this->updateItem($item,'indexer');
        }
        $this->flush(100,false);
    }

    public function deleteProduct($id)
    {
        $productData = array(
            "type"=> "product",
            "id"=> $id,
        );

        $this->deleteItem($productData);
        $this->flush(1,false);
    }

    public function getStockItem($productId)
    {
        return $this->_stockItemRepository->get($productId);
    }

    public function getProduct($productId)
    {
        return $product = $this->_productRepository->getById($productId);
    }
}
