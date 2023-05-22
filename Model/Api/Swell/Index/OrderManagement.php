<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

use Yotpo\Loyalty\Model\Api\Swell\AbstractSwell;

class OrderManagement extends AbstractSwell implements \Yotpo\Loyalty\Api\Swell\Index\OrderManagementInterface
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
    public function getOrder()
    {
        if (!$this->isAuthorized()) {
            return $this->_yotpoHelper->jsonEncode([
                "error" => 1,
                "message" => "Access Denied!"
            ]);
        }
        $collection = $this->_orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter("entity_id", $this->_yotpoHelper->getRequest()->getParam('id'))
            ->addAttributeToFilter("store_id", ["in" => $this->_yotpoHelper->getStoreIdsBySwellApiKey()])
            ->setPageSize(1);

        $response = (!$collection->count()) ? (object)[] : $this->_yotpoSchemaHelper->orderSchemaPrepare($collection->getFirstItem());
        return $this->_yotpoHelper->jsonEncode($response);
    }
}
