<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

use Yotpo\Loyalty\Model\Api\Swell\AbstractSwell;

class OrdersManagement extends AbstractSwell implements \Yotpo\Loyalty\Api\Swell\Index\OrdersManagementInterface
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory
     */
    protected $_dateTimeFactory;

    /**
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateTimeFactory
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateTimeFactory
    ) {
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_dateTimeFactory = $dateTimeFactory;
        parent::__construct($yotpoHelper, $yotpoSchemaHelper, $appEmulationHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrders()
    {
        if (!$this->isAuthorized()) {
            return $this->_yotpoHelper->jsonEncode([
                "error" => 1,
                "message" => "Access Denied!"
            ]);
        }
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

        $orderStates = array_filter(explode(',', $this->_yotpoHelper->getRequest()->getParam('state', '')));
        if (!empty($orderStates)) {
            $collection->addAttributeToFilter('state', ["in" => $orderStates]);
        }

        $createdAtFrom = $this->_yotpoHelper->getRequest()->getParam('created_at_from', '');
        if (!empty($createdAtFrom)) {
            $collection->addAttributeToFilter('created_at', ["gteq" => $this->_dateTimeFactory->create()->gmtDate('Y-m-d H:i:s', strtotime($createdAtFrom))]);
        }
        $createdAtTo = $this->_yotpoHelper->getRequest()->getParam('created_at_to', '');
        if (!empty($createdAtTo)) {
            $collection->addAttributeToFilter('created_at', ["lteq" => $this->_dateTimeFactory->create()->gmtDate('Y-m-d H:i:s', strtotime($createdAtTo))]);
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
