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
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        $this->_logger = $logger;
        $this->_registry = $registry;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->_yotpoHelper->isEnabled()) {
            try {
                if (!$this->_registry->registry("swell/order/before")) {
                    $this->_registry->register('swell/order/before', true);

                    $order = $observer->getEvent()->getOrder();
                    if ($order->isObjectNew()) {
                        $this->_registry->register('swell/order/created', true);
                        if (!$order->getData('swell_user_agent')) {
                            $order->setData('swell_user_agent', $this->_yotpoHelper->getUserAgent());
                        }
                    } else {
                        $this->_registry->register('swell/order/original/state', $order->getOrigData("state"));
                        $this->_registry->register('swell/order/original/status', $order->getOrigData("status"));
                        $this->_registry->register('swell/order/original/base_total_refunded', $order->getOrigData("base_total_refunded"));
                    }
                }
            } catch (\Exception $e) {
                $this->_yotpoHelper->log("[Yotpo - OrderSaveBefore - ERROR] " . $e->getMessage() . "\n" . print_r($e, true), "error");
            }
        }
    }
}
