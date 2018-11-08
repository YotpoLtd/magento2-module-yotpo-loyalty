<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

class OrderManagement implements \Yotpo\Loyalty\Api\Swell\Index\OrderManagementInterface
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
    public function getOrder()
    {
        $collection = $this->_orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter("entity_id", $this->_yotpoHelper->getRequest()->getParam('id'))
            ->addAttributeToFilter("store_id", ["in" => $this->_yotpoHelper->getStoreIdsBySwellApiKey()])
            ->setPageSize(1);

        $response = (!$collection->count()) ? (object)[] : $this->_yotpoSchemaHelper->orderSchemaPrepare($collection->getFirstItem());
        $this->_yotpoHelper->sendApiJsonResponse($response);
    }
}
