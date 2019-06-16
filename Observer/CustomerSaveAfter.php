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
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @method __construct
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Yotpo\Loyalty\Model\QueueFactory $yotpoQueueFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Yotpo\Loyalty\Model\QueueFactory $yotpoQueueFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoSchemaHelper = $yotpoSchemaHelper;
        $this->_yotpoQueueFactory = $yotpoQueueFactory;
        $this->_logger = $logger;
        $this->_registry = $registry;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_yotpoHelper->isEnabled()) {
            try {
                $customer = $observer->getEvent()->getCustomer();

                if (!$this->_registry->registry("swell/customer/after")) {
                    $this->_registry->register('swell/customer/after', true);
                    $customerCreated = $this->_registry->registry("swell/customer/created");
                    $emailUpdated = $customer->getData("email") != $this->_registry->registry("swell/customer/original/email");
                    $groupUpdated = $customer->getData("group_id") != $this->_registry->registry("swell/customer/original/group_id");
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
                }
            } catch (\Exception $e) {
                $this->_yotpoHelper->log("[Yotpo - CustomerSaveAfter - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            }
        }
    }
}
