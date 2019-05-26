<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

class CustomerManagement implements \Yotpo\Loyalty\Api\Swell\Index\CustomerManagementInterface
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
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $_customerCollectionFactory;

    /**
     * @param \Yotpo\Loyalty\Model\Api\Swell\Guard $swellApiGuard
     * @param \Yotpo\Loyalty\Helper\Data $yo4tpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     */
    public function __construct(
        \Yotpo\Loyalty\Model\Api\Swell\Guard $swellApiGuard,
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
    ) {
        //$swellApiGuard will be initialized from it's __construct
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoSchemaHelper = $yotpoSchemaHelper;
        $this->_customerCollectionFactory = $customerCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomer()
    {
        $collection = $this->_customerCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter("store_id", ["in" => $this->_yotpoHelper->getStoreIdsBySwellApiKey()])
            ->setPageSize(1);

        if (($id = $this->_yotpoHelper->getRequest()->getParam('id'))) {
            $collection->addAttributeToFilter("entity_id", $id);
        }

        if (($email = $this->_yotpoHelper->getRequest()->getParam('email'))) {
            $collection->addAttributeToFilter("email", $email);
        }

        $response = (!$collection->count()) ? (object)[] : $this->_yotpoSchemaHelper->customerSchemaPrepare($collection->getFirstItem());
        return $this->_yotpoHelper->jsonEncode($response);
    }
}
