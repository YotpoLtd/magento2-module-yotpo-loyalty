<?php

namespace Yotpo\Loyalty\Observer;

use Magento\Framework\Event\ObserverInterface;

class OrderSaveBefore implements ObserverInterface
{
    /**
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @method __construct
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Magento\Framework\Registry $registry
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        $this->_registry = $registry;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_yotpoHelper->isEnabled()) {
            try {
                $order = $observer->getEvent()->getOrder();
                if ($order->isObjectNew()) {
                    $this->_registry->register('swell/order/created', true, true);
                    if (!$order->getData('swell_user_agent')) {
                        $order->setData('swell_user_agent', $this->_yotpoHelper->getUserAgent());
                    }
                } elseif (
                    ($orderId = $order->getId()) &&
                    !$this->_registry->registry('swell/order/before/id' . $orderId)
                ) {
                    $this->_registry->register('swell/order/before/id' . $orderId, true);
                    $this->_registry->register('swell/order/original/state/id' . $orderId, $order->getOrigData("state"));
                    $this->_registry->register('swell/order/original/status/id' . $orderId, $order->getOrigData("status"));
                    $this->_registry->register('swell/order/original/base_total_refunded/id' . $orderId, $order->getOrigData("base_total_refunded"));
                }
            } catch (\Exception $e) {
                $this->_yotpoHelper->log("[Yotpo - OrderSaveBefore - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            }
        }
    }
}
