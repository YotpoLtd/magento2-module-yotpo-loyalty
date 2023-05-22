<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

use Yotpo\Loyalty\Model\Api\Swell\AbstractSwell;

class CustomerManagement extends AbstractSwell implements \Yotpo\Loyalty\Api\Swell\Index\CustomerManagementInterface
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
    public function getCustomer()
    {
        if (!$this->isAuthorized()) {
            return $this->_yotpoHelper->jsonEncode([
                "error" => 1,
                "message" => "Access Denied!"
            ]);
        }
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
