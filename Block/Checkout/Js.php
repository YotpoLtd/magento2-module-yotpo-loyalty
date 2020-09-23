<?php
namespace Yotpo\Loyalty\Block\Checkout;

/**
 * Class Js
 * @package Yotpo\Loyalty\Block\Checkout
 */
class Js extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Js constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = [])
    {
        parent::__construct($context, $data);
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @return mixed
     */
    public function getGuid()
    {
        return $this->_scopeConfig->getValue("yotpo_loyalty/general_settings/swell_guid");
    }

    /**
     * @return mixed
     */
    public function getInstanceId()
    {
        return $this->_scopeConfig->getValue("yotpo_loyalty/sync_advanced/swell_instance_id");
    }
}