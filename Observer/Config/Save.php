<?php

namespace Yotpo\Loyalty\Observer\Config;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class Save implements ObserverInterface
{
    const SCOPE_DEFAULT = 'default';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Yotpo\Loyalty\Helper\ApiRequest
     */
    protected $_apiRequestHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Yotpo\Loyalty\Helper\ApiRequest $apiRequestHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Yotpo\Loyalty\Helper\ApiRequest $apiRequestHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_storeManager = $storeManager;
        $this->_apiRequestHelper = $apiRequestHelper;
        $this->_messageManager = $messageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $scopeId = $observer->getEvent()->getStore();
        $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        if (!$scopeId && ($scopeId = $observer->getEvent()->getWebsite())) {
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        }
        if (!$scopeId) {
            $scope = self::SCOPE_DEFAULT;
        }
        $response = $this->_apiRequestHelper->setupRequest($scopeId, $scope);
        if ($response->getError()) {
            throw new \Exception($response->getMessage());
        } else {
            $this->_messageManager->addSuccess($response->getMessage());
        }
    }
}
