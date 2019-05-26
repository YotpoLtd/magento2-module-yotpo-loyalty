<?php

namespace Yotpo\Loyalty\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerSaveBefore implements ObserverInterface
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
                $customer = $observer->getEvent()->getCustomer();

                if (!$this->_registry->registry("swell/customer/before")) {
                    $this->_registry->register('swell/customer/before', true);
                    if ($customer->isObjectNew()) {
                        $this->_registry->register('swell/customer/created', true);
                    } else {
                        $this->_registry->register('swell/customer/original/email', $customer->getOrigData("email"));
                        $this->_registry->register('swell/customer/original/group_id', $customer->getOrigData("group_id"));
                    }
                }
            } catch (\Exception $e) {
                $this->_yotpoHelper->log("[Yotpo - CustomerSaveBefore - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            }
        }
    }
}
