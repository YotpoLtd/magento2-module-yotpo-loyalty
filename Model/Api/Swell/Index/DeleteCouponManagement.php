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
     * @var Magento\Quote\Api\CartRepositoryInterface
     */
    protected $_cartRepositoryInterface;

    /**
     * @var Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper
     * @param \Magento\SalesRule\Model\CouponFactory $couponFactory
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $_cartRepositoryInterface
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $_searchCriteriaBuilder
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper,
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\Quote\Api\CartRepositoryInterface $_cartRepositoryInterface,
        \Magento\Framework\Api\SearchCriteriaBuilder $_searchCriteriaBuilder
    ) {
        $this->_couponFactory = $couponFactory;
        $this->_ruleFactory = $ruleFactory;
        $this->_cartRepositoryInterface = $_cartRepositoryInterface;
        $this->_searchCriteriaBuilder = $_searchCriteriaBuilder;
        parent::__construct($yotpoHelper, $yotpoSchemaHelper, $appEmulationHelper);
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
            $couponIds = $this->_yotpoHelper->getRequest()->getParam('id', '');
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
                    $quotes = $this->getQuotesByCouponId($coupon->getCode());
                    if ($quotes) {
                        foreach ($quotes as $quote) {
                            $quote->setCouponCode('')
                                ->collectTotals()
                                ->save();
                        }
                    }
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

    /**
     * @param $couponCode
     * @return false|\Magento\Quote\Api\Data\CartInterface[]
     */
    public function getQuotesByCouponId($couponCode)
    {
        if (!empty($couponCode)) {
            $searchCriteria = $this->_searchCriteriaBuilder->addFilter('coupon_code', $couponCode, 'eq')->create();
            $quotes = $this->_cartRepositoryInterface->getList($searchCriteria)->getItems();
            return $quotes;
        } else {
            return false;
        }
    }
}
