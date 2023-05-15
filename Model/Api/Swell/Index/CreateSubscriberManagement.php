<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

use Yotpo\Loyalty\Model\Api\Swell\AbstractSwell;

class CreateSubscriberManagement extends AbstractSwell implements \Yotpo\Loyalty\Api\Swell\Index\CreateSubscriberManagementInterface
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $_customerCollectionFactory;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $_subscriberFactory;

    /**
     * @var \Magento\Newsletter\Model\Subscriber
     */
    protected $_subscriberModel;

    /**
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Newsletter\Model\Subscriber $subscriberModel
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Newsletter\Model\Subscriber $subscriberModel
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoSchemaHelper = $yotpoSchemaHelper;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_subscriberModel = $subscriberModel;
        parent::__construct($yotpoHelper, $yotpoSchemaHelper, $appEmulationHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function postCreateSubscriber()
    {
        if (!$this->isAuthorized()) {
            return $this->_yotpoHelper->jsonEncode([
                "error" => 1,
                "message" => "Access Denied!"
            ]);
        }
        try {
            if (!($email = $this->_yotpoHelper->getRequest()->getParam('email'))) {
                return $this->_yotpoHelper->jsonEncode([
                    "error" => "`email` is a required field!"
                ]);
            }

            $subscriber = $this->_subscriberModel->loadByEmail($email);

            if (!$subscriber->isSubscribed()) {
                $this->_subscriberFactory->create()->subscribe($email);
                $subscriber = $this->_subscriberModel->loadByEmail($email);
            }

            if ($subscriber->isSubscribed()) {
                $collection = $this->_customerCollectionFactory->create()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter("store_id", ["in" => $this->_yotpoHelper->getStoreIdsBySwellApiKey()])
                    ->addAttributeToFilter("email", $email)
                    ->setPageSize(1);

                if ($collection->count()) {
                    $subscriber
                        ->setCustomerId($collection->getFirstItem()->getId())
                        ->save();
                    return $this->_yotpoHelper->jsonEncode($this->_yotpoSchemaHelper->customerSchemaPrepare($collection->getFirstItem()));
                } else {
                    return $this->_yotpoHelper->jsonEncode($subscriber->getData());
                }
            }
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("[Yotpo Loyalty API - CreateSubscriber - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            return $this->_yotpoHelper->jsonEncode([
                "error" => 'An error has occurred trying to subscribe ' . $email
            ]);
        }
        return $this->_yotpoHelper->jsonEncode([]);
    }
}
