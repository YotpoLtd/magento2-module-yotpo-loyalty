<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

class DeleteCouponManagement implements \Yotpo\Loyalty\Api\Swell\Index\DeleteCouponManagementInterface
{
    /**
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @var \Yotpo\Loyalty\Helper\Schema
     */
    protected $_yotpoSchemaHelper;

    /**
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $_couponFactory;

    /**
     * @param \Yotpo\Loyalty\Model\Api\Swell\Guard $swellApiGuard
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Magento\SalesRule\Model\CouponFactory $couponFactory
     */
    public function __construct(
        \Yotpo\Loyalty\Model\Api\Swell\Guard $swellApiGuard,
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Magento\SalesRule\Model\CouponFactory $couponFactory
    ) {
        //$swellApiGuard will be initialized from it's __construct
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoSchemaHelper = $yotpoSchemaHelper;
        $this->_couponFactory = $couponFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function postDeleteCoupon()
    {
        try {
            //Extract Request Params:
            $couponId = $this->_yotpoHelper->getRequest()->getParam('id');
            //===========================================================================================//

            if (!$couponId) {
                return $this->_yotpoHelper->jsonEncode([
                    "error" => "`id` is a required field!"
                ]);
            }

            $coupon = $this->_couponFactory->create()->load($couponId);
            if ($coupon->getId() && $coupon->getRuleId()) {
                $coupon->delete();
            } else {
                return $this->_yotpoHelper->jsonEncode([
                    "success" => true,
                    "message" => 'There is no coupon with this id'
                ]);
            }
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("[Yotpo Loyalty API - DeleteCoupon - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            return $this->_yotpoHelper->jsonEncode([
                "error" => 'An error has occurred while trying to delete coupon ID' . $couponId
            ]);
        }
        return $this->_yotpoHelper->jsonEncode([
            "success" => true
        ]);
    }
}
