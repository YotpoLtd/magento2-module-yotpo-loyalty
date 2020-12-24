<?php
namespace Yotpo\Loyalty\Block\Checkout;

/**
 * Class Js
 * @package Yotpo\Loyalty\Block\Checkout
 */
class Js extends \Magento\Framework\View\Element\Template
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

    /**
     * @return mixed
     */
    public function getGuid()
    {
        return $this->_yotpoHelper->getSwellGuid();
    }

    /**
     * @return mixed
     */
    public function getInstanceId()
    {
        return $this->_yotpoHelper->getSwellInstanceId();
    }
}
