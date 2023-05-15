<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

use Yotpo\Loyalty\Model\Api\Swell\AbstractSwell;

class CustomersManagement extends AbstractSwell implements \Yotpo\Loyalty\Api\Swell\Index\CustomersManagementInterface
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $_customerCollectionFactory;

    /**
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
    ) {
        $this->_customerCollectionFactory = $customerCollectionFactory;
        parent::__construct($yotpoHelper, $yotpoSchemaHelper, $appEmulationHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomers()
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
