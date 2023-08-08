<?php

namespace Yotpo\Loyalty\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class CustomerSaveBefore implements ObserverInterface
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
        try {
            $customer = $observer->getEvent()->getCustomer();
            $storeId = $customer->getData('store_id') ?: $this->_yotpoHelper->getCurrentStoreId();
            if ($this->_yotpoHelper->isEnabled(ScopeInterface::SCOPE_STORE, $storeId)) {
                if ($customer->isObjectNew()) {
                    $this->_registry->register('swell/customer/created', true, true);
                } elseif (
                    ($customerId = $customer->getId()) &&
                    !$this->_registry->registry('swell/customer/before/id' . $customerId)
                ) {
                    $this->_registry->register('swell/customer/before/id' . $customerId, true);
                    $this->_registry->register('swell/customer/original/email/id' . $customerId, $customer->getOrigData("email"));
                    $this->_registry->register('swell/customer/original/group_id/id' . $customerId, $customer->getOrigData("group_id"));
                }
            }
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("[Yotpo - CustomerSaveBefore - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }
    }
}
