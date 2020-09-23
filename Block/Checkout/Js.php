<?php
namespace Yotpo\Loyalty\Block\Checkout;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Js extends \Magento\Framework\View\Element\Template
{
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->_scopeConfig = $scopeConfig;
    }

    public function getGuid()
    {
        return $this->_scopeConfig->getValue("yotpo_loyalty/general_settings/swell_guid");
    }

    public function getInstanceId()
    {
        return $this->_scopeConfig->getValue("yotpo_loyalty/sync_advanced/swell_instance_id");
    }
}