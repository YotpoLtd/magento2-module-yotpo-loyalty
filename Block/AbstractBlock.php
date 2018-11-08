<?php

namespace Yotpo\Loyalty\Block;

class AbstractBlock extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @method __construct
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Yotpo\Loyalty\Helper\Data $yotpoHelper
    ) {
        parent::__construct($context);
        $this->_yotpoHelper = $yotpoHelper;
    }

    public function isEnabled()
    {
        return $this->_yotpoHelper->isEnabled();
    }

    public function getSwellGuid()
    {
        return $this->_yotpoHelper->getSwellGuid();
    }

    public function isDebugMode()
    {
        return $this->_yotpoHelper->isDebugMode();
    }
}
