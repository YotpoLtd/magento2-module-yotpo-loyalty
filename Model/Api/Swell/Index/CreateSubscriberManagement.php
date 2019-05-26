<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

class CreateSubscriberManagement implements \Yotpo\Loyalty\Api\Swell\Index\CreateSubscriberManagementInterface
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
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $_subscriberFactory;

    /**
     * @var \Magento\Newsletter\Model\Subscriber
     */
    protected $_subscriberModel;

    /**
     * @param \Yotpo\Loyalty\Model\Api\Swell\Guard $swellApiGuard
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Newsletter\Model\Subscriber $subscriberModel
     */
    public function __construct(
        \Yotpo\Loyalty\Model\Api\Swell\Guard $swellApiGuard,
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Newsletter\Model\Subscriber $subscriberModel
    ) {
        //$swellApiGuard will be initialized from it's __construct
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoSchemaHelper = $yotpoSchemaHelper;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_subscriberModel = $subscriberModel;
    }

    /**
     * {@inheritdoc}
     */
    public function postCreateSubscriber()
    {
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
            $this->_yotpoHelper->log("[Yotpo Loyalty API - CreateSubscriber - ERROR] " . $e->getMessage() . "\n" . print_r($e, true), "error");
            return $this->_yotpoHelper->jsonEncode([
                "error" => 'An error has occurred trying to subscribe ' . $email
            ]);
        }
        return $this->_yotpoHelper->jsonEncode([]);
    }
}
