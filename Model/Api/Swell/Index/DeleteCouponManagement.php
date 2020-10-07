<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

use Yotpo\Loyalty\Model\Api\Swell\AbstractSwell;

class DeleteCouponManagement extends AbstractSwell implements \Yotpo\Loyalty\Api\Swell\Index\DeleteCouponManagementInterface
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
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $_ruleFactory;

    /**
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Magento\SalesRule\Model\CouponFactory $couponFactory
     * @param \Magento\SalesRule\Model\RuleFactory $couponFactory
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory
    ) {
        $this->_couponFactory = $couponFactory;
        $this->_ruleFactory = $ruleFactory;
        parent::__construct($yotpoHelper, $yotpoSchemaHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function postDeleteCoupon()
    {
        if (!$this->isAuthorized()) {
            return $this->_yotpoHelper->jsonEncode([
                "error" => 1,
                "message" => "Access Denied!"
            ]);
        }
        try {
            $response = [
                "success" => true,
                "message" => "Success",
                "data" => [],
            ];

            //Extract Request Params:
            $couponIds = $this->_yotpoHelper->getRequest()->getParam('id');
            if (!(is_array($couponIds) || is_object($couponIds))) {
                $couponIds = explode(",", $couponIds);
            }
            $couponIds = array_filter(array_map('trim', (array) $couponIds));

            $deleteRule = (bool) $this->_yotpoHelper->getRequest()->getParam('delete_rule');
            //===========================================================================================//

            if (!$couponIds) {
                return $this->_yotpoHelper->jsonEncode([
                    "error" => "`id` is a required field!"
                ]);
            }

            foreach ($couponIds as $couponId) {
                try {
                    $coupon = $this->_couponFactory->create()->load($couponId);
                    $ruleId = $coupon->getRuleId();
                    if ($coupon->getId() && $coupon->getRuleId()) {
                        $coupon->delete();
                        $response["data"][$couponId] = [
                            "success" => true,
                            "rule_id" => $ruleId,
                        ];
                    } else {
                        $response["data"][$couponId] = [
                            "success" => true,
                            "message" => 'There is no coupon with this id'
                        ];
                    }
                    $response["data"][$couponId]["rule_deleted"] = false;
                    if ($ruleId && $deleteRule) {
                        $rule = $this->_ruleFactory->create()->load($ruleId);
                        if ($rule->getId() && $rule->getRuleId()) {
                            $rule->delete();
                            $response["data"][$couponId]["rule_deleted"] = true;
                        }
                    }
                } catch (\Exception $e) {
                    $this->_yotpoHelper->log("[Yotpo Loyalty API - DeleteCoupon - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
                    $response["data"][$couponId] = [
                        "error" => 'An error has occurred while trying to delete coupon ID' . $couponId
                    ];
                    $response["success"] = false;
                    $response["error"] = true;
                    $response["message"] = "Has errors";
                }
            }
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("[Yotpo Loyalty API - DeleteCoupon - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            return $this->_yotpoHelper->jsonEncode([
                "error" => 'An error has occurred while trying to delete coupon(s)' . $couponId,
                "data" => $couponIds
            ]);
        }

        return $this->_yotpoHelper->jsonEncode($response);
    }
}
