<?php

namespace Yotpo\Loyalty\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

class ModuleVersion extends AbstractField
{
    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return "<div>{$this->_yotpoHelper->getModuleVersion()}</div>";
    }
}
