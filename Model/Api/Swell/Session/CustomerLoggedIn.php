<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Session;

class CustomerLoggedIn implements \Yotpo\Loyalty\Api\Swell\Session\CustomerLoggedInInterface
{

    /**
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_customerSession;

    /**
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Magento\Customer\Model\Session customerSession
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        $this->_customerSession = $customerSession;
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerLoggedIn()
    {
        try {
            if (!$this->_yotpoHelper->isEnabled()) {
                throw new \Exception('The Yotpo Loyalty module has been disabled from store configuration.');
            }
            if ($this->_customerSession->isLoggedIn()) {
                return $this->_yotpoHelper->jsonEncode([
                    "email" => $this->_customerSession->getCustomer()->getEmail(),
                    "id" => $this->_customerSession->getId()
                ]);
            }
            return $this->_yotpoHelper->jsonEncode([]);
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("[Yotpo Loyalty API - CustomerLoggedIn - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            return $this->_yotpoHelper->jsonEncode([
                "error" => true
            ]);
        }

        return $this->_yotpoHelper->jsonEncode([]);
    }
}
