<?php

namespace Yotpo\Loyalty\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class DirectLink extends Field
{
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'Yotpo_Loyalty::system/config/advance/direct_link.phtml';

    /**
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $_request;

    protected $_websiteId;
    protected $_storeId;

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Magento\Framework\App\Request\Http $request,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_yotpoHelper = $yotpoHelper;
        $this->_request = $request;
        $this->_websiteId = $request->getParam('website');
        $this->_storeId = $this->getRequest()->getParam('store');
    }

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getSwellGuid()
    {
        if ($this->_storeId !== null) {
            return $this->_yotpoHelper->getSwellGuid(\Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->_storeId);
        } elseif ($this->_websiteId !== null) {
            return $this->_yotpoHelper->getSwellGuid(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $this->_websiteId);
        } else {
            return $this->_yotpoHelper->getSwellGuid();
        }
    }

    public function getSwellApiKey()
    {
        if ($this->_storeId !== null) {
            return $this->_yotpoHelper->getSwellApiKey(\Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->_storeId);
        } elseif ($this->_websiteId !== null) {
            return $this->_yotpoHelper->getSwellApiKey(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $this->_websiteId);
        } else {
            return $this->_yotpoHelper->getSwellApiKey();
        }
    }

    /**
     * Generate collect button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        )->setData(
            [
                'id' => 'swell_direct_login_link_button',
                'label' => __('Log in to Swell')
            ]
        );
        if (!($guid = $this->getSwellGuid()) || !($apiKey = $this->getSwellApiKey())) {
            $button->setOnClick("window.open('https://loyalty.yotpo.com/login','_blank');");
        } else {
            $button->setOnClick("window.open('https://loyalty.yotpo.com/login/{$guid}/{$apiKey}','_blank');");
        }

        return $button->toHtml();
    }
}
