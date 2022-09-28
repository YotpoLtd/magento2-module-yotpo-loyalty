<?php

namespace Yotpo\Loyalty\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerSaveAfter implements ObserverInterface
{
    /**
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @var \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     */
    protected $_yotpoSchemaHelper;

    /**
     * @var \Yotpo\Loyalty\Model\QueueFactory
     */
    protected $_yotpoQueueFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @method __construct
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Yotpo\Loyalty\Model\QueueFactory $yotpoQueueFactory
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Yotpo\Loyalty\Model\QueueFactory $yotpoQueueFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoSchemaHelper = $yotpoSchemaHelper;
        $this->_yotpoQueueFactory = $yotpoQueueFactory;
        $this->_registry = $registry;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_yotpoHelper->isEnabled()) {
            try {
                $customer = $observer->getEvent()->getCustomer();

                $customerId = $customer->getId();
                $newEmail = $customer->getData("email");
                $newGroup = $customer->getData("group_id");

                $customerCreated = $this->_registry->registry('swell/customer/created');
                $emailUpdated = $newEmail != $this->_registry->registry('swell/customer/original/email/id' . $customerId);
                $groupUpdated = $newGroup != $this->_registry->registry('swell/customer/original/group_id/id' . $customerId);
                $customerUpdated = $emailUpdated || $groupUpdated;

                if ($customerCreated || $customerUpdated) {
                    $entityStatus = $customerCreated ? "created" : "updated";
                    $preparedData = $this->_yotpoSchemaHelper->customerSchemaPrepare($customer, $entityStatus);
                    $queueItem = $this->_yotpoQueueFactory->create()
                        ->setEntityType("customer")
                        ->setEntityId($customer->getId())
                        ->setEntityStatus($entityStatus)
                        ->setStoreId($this->_yotpoHelper->getCurrentStoreId())
                        ->setCreatedAt($this->_yotpoHelper->getCurrentDate())
                        ->setPreparedSchema($preparedData)
                        ->save();
                }

                if ($customerCreated) {
                    $this->_registry->unregister('swell/customer/created');
                }
                if ($customerUpdated) {
                    $this->_registry->unregister('swell/customer/original/email');
                    $this->_registry->unregister('swell/customer/original/group_id');
                    $this->_registry->register('swell/customer/original/email', $newEmail);
                    $this->_registry->register('swell/customer/original/group_id', $newGroup);
                }
            } catch (\Exception $e) {
                $this->_yotpoHelper->log("[Yotpo - CustomerSaveAfter - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            }
        }
    }
}
