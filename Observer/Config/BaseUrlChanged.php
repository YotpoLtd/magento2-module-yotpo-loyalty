<?php

namespace Yotpo\Loyalty\Observer\Config;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class BaseUrlChanged implements ObserverInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Yotpo\Loyalty\Helper\ApiRequest
     */
    protected $_apiRequestHelper;

    /**
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Yotpo\Loyalty\Helper\ApiRequest $apiRequestHelper
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Yotpo\Loyalty\Helper\ApiRequest $apiRequestHelper,
        \Yotpo\Loyalty\Helper\Data $yotpoHelper
    ) {
        $this->_storeManager = $storeManager;
        $this->_apiRequestHelper = $apiRequestHelper;
        $this->_yotpoHelper = $yotpoHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        try {
            $scopeId = $observer->getEvent()->getStore();
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            if (!$scopeId && ($scopeId = $observer->getEvent()->getWebsite())) {
                $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
            }
            if (!$scopeId) {
                $scope = \Magento\Framework\App\ScopeInterface::SCOPE_DEFAULT;
            }
            if ($this->_yotpoHelper->isEnabled($scope, $scopeId) &&
                $this->_yotpoHelper->isStoreInformationWebhhoksEnabled($scope, $scopeId)
            ) {
                $response = $this->_apiRequestHelper->storeInformationWebhookRequest($scopeId, $scope);
                if ($response->getError()) {
                    throw new \Exception($response->getMessage());
                } else {
                    $this->_yotpoHelper->log("[Observer\Config\BaseUrlChanged] Successfully sent store information webhhok.", "info");
                }
            }
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("[Observer\Config\BaseUrlChanged - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }
    }
}
