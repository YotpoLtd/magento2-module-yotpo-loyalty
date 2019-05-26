<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

class OrdersManagement implements \Yotpo\Loyalty\Api\Swell\Index\OrdersManagementInterface
{

    /**
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @var \Yotpo\Loyalty\Helper\Schema
     */
    protected $_yotpoSchemaHelper;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @param \Yotpo\Loyalty\Model\Api\Swell\Guard $swellApiGuard
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        \Yotpo\Loyalty\Model\Api\Swell\Guard $swellApiGuard,
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        //$swellApiGuard will be initialized from it's __construct
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoSchemaHelper = $yotpoSchemaHelper;
        $this->_orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrders()
    {
        $page = $this->_yotpoHelper->getRequest()->getParam('page');
        if (!is_numeric($page)) {
            $page = 1;
        }
        $pageSize = $this->_yotpoHelper->getRequest()->getParam('page_size');
        if (!is_numeric($pageSize)) {
            $pageSize = 250;
        }

        $collection = $this->_orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter("store_id", ["in" => $this->_yotpoHelper->getStoreIdsBySwellApiKey()]);

        $orderStates = array_filter(explode(',', $this->_yotpoHelper->getRequest()->getParam('state')));
        if (!empty($orderStates)) {
            $collection->addAttributeToFilter('state', ["in" => $orderStates]);
        }

        $collection->setPageSize($pageSize);
        $collection->setCurPage($page);

        return $this->_yotpoHelper->jsonEncode([
            "orders" => $this->_yotpoSchemaHelper->ordersSchemaPrepare($collection),
            "last_page" => $collection->getLastPageNumber(),
            "current_page" => $page,
        ]);
    }
}
