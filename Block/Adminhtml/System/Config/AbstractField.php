<?php

namespace Yotpo\Loyalty\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\ScopeInterface;
use Yotpo\Loyalty\Helper\Data as YotpoHelper;

class AbstractField extends Field
{
    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * @var int|null
     */
    protected $_websiteId;

    /**
     * @var int|null
     */
    protected $_storeId;

    /**
     * @method __construct
     * @param  Context     $context
     * @param  YotpoHelper $yotpoHelper
     * @param  array       $data
     */
    public function __construct(
        Context $context,
        YotpoHelper $yotpoHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_yotpoHelper = $yotpoHelper;
        $this->_websiteId = $this->getRequest()->getParam('website');
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

    /**
     * @method getSwellGuid
     * @return string
     */
    public function getSwellGuid()
    {
        if ($this->_storeId !== null) {
            return $this->_yotpoHelper->getSwellGuid(ScopeInterface::SCOPE_STORE, $this->_storeId);
        } elseif ($this->_websiteId !== null) {
            return $this->_yotpoHelper->getSwellGuid(ScopeInterface::SCOPE_WEBSITE, $this->_websiteId);
        } else {
            return $this->_yotpoHelper->getSwellGuid();
        }
    }

    /**
     * @method getSwellApiKey
     * @return string
     */
    public function getSwellApiKey()
    {
        if ($this->_storeId !== null) {
            return $this->_yotpoHelper->getSwellApiKey(ScopeInterface::SCOPE_STORE, $this->_storeId);
        } elseif ($this->_websiteId !== null) {
            return $this->_yotpoHelper->getSwellApiKey(ScopeInterface::SCOPE_WEBSITE, $this->_websiteId);
        } else {
            return $this->_yotpoHelper->getSwellApiKey();
        }
    }

    /**
     * @method getRootApiUrl
     * @return string
     */
    public function getRootApiUrl()
    {
        if ($this->_storeId !== null) {
            return rtrim($this->_yotpoHelper->getBaseUrl(ScopeInterface::SCOPE_STORE, $this->_storeId), "/") . "/rest/V1";
        } elseif ($this->_websiteId !== null) {
            return rtrim($this->_yotpoHelper->getBaseUrl(ScopeInterface::SCOPE_WEBSITE, $this->_websiteId), "/") . "/rest/V1";
        } else {
            return rtrim($this->_yotpoHelper->getBaseUrl(), "/") . "/rest/V1";
        }
    }
}
