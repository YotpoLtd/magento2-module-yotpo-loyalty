<?php

namespace Yotpo\Loyalty\Observer;

use Magento\Framework\Event\ObserverInterface;

class OrderSaveAfter implements ObserverInterface
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
                $order = $observer->getEvent()->getOrder();

                $originalState = $this->_registry->registry('swell/order/original/state');
                $originalStatus = $this->_registry->registry('swell/order/original/status');
                $originalTotalRefunded = $this->_registry->registry('swell/order/original/base_total_refunded');
                $orderCreated = $this->_registry->registry('swell/order/created');

                $newState = $order->getData("state");
                $newStatus = $order->getData("status");
                $newTotalRefunded = $order->getData("base_total_refunded");

                $stateUpdated = isset($originalState) && $originalState != $newState;
                $statusUpdated = isset($originalStatus) && $originalStatus != $newStatus;
                $refundUpdated = isset($originalTotalRefunded) && $originalTotalRefunded != $newTotalRefunded;
                $refundCreated = !isset($originalTotalRefunded) && isset($newTotalRefunded);
                $orderUpdated = $stateUpdated || $statusUpdated;
                $orderRefunded = $refundCreated || $refundUpdated;

                if ($orderCreated || $orderUpdated || $orderRefunded) {
                    if ($orderCreated) {
                        $entityStatus = "created";
                    } elseif ($orderRefunded) {
                        $entityStatus = "refunded";
                    } elseif ($orderUpdated) {
                        $entityStatus = "updated";
                    }
                    $preparedData = $this->_yotpoSchemaHelper->orderSchemaPrepare($order, $entityStatus);
                    $queueItem = $this->_yotpoQueueFactory->create()
                        ->setEntityType("order")
                        ->setEntityId($order->getId())
                        ->setEntityStatus($entityStatus)
                        ->setStoreId($this->_yotpoHelper->getCurrentStoreId())
                        ->setCreatedAt($this->_yotpoHelper->getCurrentDate())
                        ->setPreparedSchema($preparedData)
                        ->save();
                }

                if ($orderCreated) {
                    $this->_registry->unregister('swell/order/created');
                }
                if ($orderRefunded) {
                    $this->_registry->unregister('swell/order/original/base_total_refunded');
                    $this->_registry->register('swell/order/original/base_total_refunded', $newTotalRefunded);
                }
                if ($orderUpdated) {
                    $this->_registry->unregister('swell/order/original/state');
                    $this->_registry->unregister('swell/order/original/status');
                    $this->_registry->register('swell/order/original/state', $newState);
                    $this->_registry->register('swell/order/original/status', $newStatus);
                }
            } catch (\Exception $e) {
                $this->_yotpoHelper->log("[Yotpo - OrderSaveAfter - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            }
        }
    }
}
