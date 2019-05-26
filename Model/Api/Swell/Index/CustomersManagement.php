<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

class CustomersManagement implements \Yotpo\Loyalty\Api\Swell\Index\CustomersManagementInterface
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
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
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
    public function getCustomers()
    {
        $page = $this->_yotpoHelper->getRequest()->getParam('page');
        if (!is_numeric($page)) {
            $page = 1;
        }
        $pageSize = $this->_yotpoHelper->getRequest()->getParam('page_size');
        if (!is_numeric($pageSize)) {
            $pageSize = 250;
        }

        $collection = $this->_customerCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter("store_id", ["in" => $this->_yotpoHelper->getStoreIdsBySwellApiKey()]);

        $collection->setPageSize($pageSize);
        $collection->setCurPage($page);

        return $this->_yotpoHelper->jsonEncode([
            "customers" => $this->_yotpoSchemaHelper->customersSchemaPrepare($collection),
            "last_page" => $collection->getLastPageNumber(),
            "current_page" => $page,
        ]);
    }
}
