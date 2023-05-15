<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

use Yotpo\Loyalty\Model\Api\Swell\AbstractSwell;

class ThirtyDayOrderVolumeManagement extends AbstractSwell implements \Yotpo\Loyalty\Api\Swell\Index\ThirtyDayOrderVolumeManagementInterface
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    ) {
        $this->_orderCollectionFactory = $orderCollectionFactory;
        parent::__construct($yotpoHelper, $yotpoSchemaHelper, $appEmulationHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function getThirtyDayOrderVolume()
    {
        if (!$this->isAuthorized()) {
            return $this->_yotpoHelper->jsonEncode([
                "error" => 1,
                "message" => "Access Denied!"
            ]);
        }
        $collection = $this->_orderCollectionFactory->create()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToFilter("store_id", ["in" => $this->_yotpoHelper->getStoreIdsBySwellApiKey()])
            ->addAttributeToFilter('created_at', ['from' => date('Y-m-d', strtotime("-30 day"))]);

        $orderStates = array_filter(explode(',', $this->_yotpoHelper->getRequest()->getParam('state', '')));
        if (!empty($orderStates)) {
            $collection->addAttributeToFilter('state', ["in" => $orderStates]);
        }

        return $this->_yotpoHelper->jsonEncode([
            "orders" => $collection->count()
        ]);
    }
}
