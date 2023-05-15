<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

use Yotpo\Loyalty\Model\Api\Swell\AbstractSwell;

class OrderCountManagement extends AbstractSwell implements \Yotpo\Loyalty\Api\Swell\Index\OrderCountManagementInterface
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
    public function getOrderCount()
    {
        if (!$this->isAuthorized()) {
            return $this->_yotpoHelper->jsonEncode([
                "error" => 1,
                "message" => "Access Denied!"
            ]);
        }
        $collection = $this->_orderCollectionFactory->create()
            ->addAttributeToSelect('entity_id')
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

        return $this->_yotpoHelper->jsonEncode([
            "orders" => $collection->getSize()
        ]);
    }
}
