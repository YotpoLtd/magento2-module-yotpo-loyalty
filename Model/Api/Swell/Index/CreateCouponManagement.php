<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

use Yotpo\Loyalty\Model\Api\Swell\AbstractSwell;

class CreateCouponManagement extends AbstractSwell implements \Yotpo\Loyalty\Api\Swell\Index\CreateCouponManagementInterface
{
    /**
     * @var \Magento\SalesRule\Model\Rule
     */
    protected $_ruleModel;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $_ruleFactory;

    /**
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $_couponFactory;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\Collection
     */
    protected $_customerGroupCollection;

    /**
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper
     * @param \Magento\SalesRule\Model\Rule $ruleModel
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Magento\SalesRule\Model\CouponFactory $couponFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Customer\Model\ResourceModel\Group\Collection
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper,
        \Magento\SalesRule\Model\Rule $ruleModel,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Customer\Model\ResourceModel\Group\Collection $customerGroupCollection
    ) {
        $this->_ruleModel = $ruleModel;
        $this->_ruleFactory = $ruleFactory;
        $this->_couponFactory = $couponFactory;
        $this->_quoteFactory = $quoteFactory;
        $this->_customerGroupCollection = $customerGroupCollection;
        parent::__construct($yotpoHelper, $yotpoSchemaHelper, $appEmulationHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function postCreateCoupon()
    {
        if (!$this->isAuthorized()) {
            return $this->_yotpoHelper->jsonEncode([
                "error" => 1,
                "message" => "Access Denied!"
            ]);
        }
        try {
            //Extract Request Params:
            $discountType = $this->_yotpoHelper->getRequest()->getParam('discount_type');
            $code = $this->_yotpoHelper->getRequest()->getParam('code');
            $name = $this->_yotpoHelper->getRequest()->getParam('name');
            $thirdPartyId = $this->_yotpoHelper->getRequest()->getParam('third_party_id');
            $value = $this->_yotpoHelper->getRequest()->getParam('value');
            $appliesToAttributes = $this->_yotpoHelper->getRequest()->getParam('applies_to_attributes', '');
            $appliesToValues = $this->_yotpoHelper->getRequest()->getParam('applies_to_values', '');
            $appliesToAnyOrAllAttributes = $this->_yotpoHelper->getRequest()->getParam('applies_to_any_or_all_attributes');
            $groupIds = $this->_yotpoHelper->getRequest()->getParam('group_ids', '');
            $usageLimit = $this->_yotpoHelper->getRequest()->getParam('usage_limit');
            $oncePerCustomer = $this->_yotpoHelper->getRequest()->getParam('once_per_customer');
            $freeShippingLessThanCents = $this->_yotpoHelper->getRequest()->getParam('free_shipping_less_than_cents');
            $cartGreaterThan = $this->_yotpoHelper->getRequest()->getParam('cart_greater_than');
            $quoteId = $this->_yotpoHelper->getRequest()->getParam('quote_id');
            //===========================================================================================//

            if (!$code) {
                return $this->_yotpoHelper->jsonEncode([
                    "error" => "`code` is a required field!"
                ]);
            }

            //Make sure that the coupon code doesn't exist:
            $coupon = $this->_couponFactory->create()->loadByCode($code);
            if ($coupon && $coupon->getId()) {
                $this->_yotpoHelper->log("[Yotpo Loyalty API - CreateCoupon - ERROR] Coupon code '{$code}' already exists.", "error");
                return $this->_yotpoHelper->jsonEncode([
                    "error" => "An error has occurred trying to add coupon to cart: Coupon code '{$code}' already exists."
                ]);
            }

            //Check if the request params contains a specific rule_id:
            if ($thirdPartyId) {
                //Load Rule by given third_party_id
                $rule = $this->_ruleFactory->create()->load($thirdPartyId);
            } else {
                //Prepare Rule Params
                $appliesToAttributes = (is_array($appliesToAttributes)) ? array_values(array_filter($appliesToAttributes, 'strlen')) : array_values(array_filter(explode(",", (string)$appliesToAttributes), 'strlen'));
                $appliesToValues = (is_array($appliesToValues)) ? array_values(array_filter($appliesToValues, 'strlen')) : array_values(array_filter(explode(",", (string)$appliesToValues), 'strlen'));
                $groupIds = (is_array($groupIds)) ? array_filter($groupIds, 'strlen') : array_filter(explode(",", (string)$groupIds), 'strlen');

                if (!$groupIds) {
                    $groupIds = $this->_customerGroupCollection->getAllIds();
                }

                if ($appliesToAnyOrAllAttributes === null) {
                    $appliesToAnyOrAllAttributes = 'all';
                }
                switch ($discountType) {
                    case 'fixed_amount':
                        $simpleAction = \Magento\SalesRule\Api\Data\RuleInterface::DISCOUNT_ACTION_FIXED_AMOUNT_FOR_CART;
                        break;
                    case 'fixed':
                        $simpleAction = \Magento\SalesRule\Api\Data\RuleInterface::DISCOUNT_ACTION_FIXED_AMOUNT;
                        break;
                    case 'buy_x_get_y':
                        $simpleAction = \Magento\SalesRule\Api\Data\RuleInterface::DISCOUNT_ACTION_BUY_X_GET_Y;
                        break;
                    case 'percentage':
                    default:
                        $simpleAction = \Magento\SalesRule\Api\Data\RuleInterface::DISCOUNT_ACTION_BY_PERCENT;
                }

                //Create New Rule
                try {
                    $ruleData = [
                        "name" => $name,
                        "description" => $name,
                        "uses_per_coupon" => $usageLimit,
                        "is_active" => "1",
                        "stop_rules_processing" => "0",
                        "is_advanced" => "1",
                        "product_ids" => null,
                        "sort_order" => "0",
                        "simple_action" => $simpleAction,
                        "discount_amount" => $value,
                        "apply_to_shipping" => "0",
                        "times_used" => "0",
                        "is_rss" => "0",
                        "coupon_type" => \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC,
                        "use_auto_generation" => 1,
                        "simple_free_shipping" => "0",
                        "customer_group_ids" => $groupIds,
                        "website_ids" => $this->_yotpoHelper->getWebsiteIdsBySwellApiKey(),
                        "coupon_code" => null,
                    ];
                    if ($oncePerCustomer == "true") {
                        $ruleData['uses_per_customer'] = 1;
                    }

                    $conditions = $actions = [];

                    if (count($appliesToAttributes) || $cartGreaterThan !== null) {
                        $conditions["1"] = $actions["1"] = [
                            "type" => \Magento\SalesRule\Model\Rule\Condition\Combine::class,
                            "aggregator" => 'all',
                            "value" => 1,
                            "new_child" => ""
                        ];
                    }

                    if (count($appliesToAttributes)) {
                        $conditions["1--1"] = [
                            "type" => \Magento\SalesRule\Model\Rule\Condition\Product\Found::class,
                            "aggregator" => $appliesToAnyOrAllAttributes,
                            "value" => 1,
                            "new_child" => ""
                        ];
                        foreach ($appliesToAttributes as $index => $appliesToAttribute) {
                            $appliesToValue = $appliesToValues[$index];
                            $conditions["1--1--" . ($index+1)] = [
                                "type" => \Magento\SalesRule\Model\Rule\Condition\Product::class,
                                "attribute" => $appliesToAttribute,
                                "operator" => "==",
                                "value" => $appliesToValue
                            ];
                            $actions["1--" . ($index+1)] = [
                                "type" => \Magento\SalesRule\Model\Rule\Condition\Product::class,
                                "attribute" => $appliesToAttribute,
                                "operator" => "==",
                                "value" => $appliesToValue
                            ];
                        }
                    }

                    if ($cartGreaterThan !== null) {
                        $index = 1;
                        for ($i=1; $i < 200; $i++) {
                            if (!isset($conditions["1--" . $i])) {
                                $conditions["1--" . $i] = [
                                    "type" => \Magento\SalesRule\Model\Rule\Condition\Address::class,
                                    "attribute" => 'base_subtotal',
                                    "operator" => '>',
                                    "value" => $cartGreaterThan
                                ];
                                break;
                            }
                        }
                    }

                    if ($conditions || $actions) {
                        $ruleData['conditions'] = $conditions;
                        $ruleData['actions'] = $actions;
                    }

                    $rule = $this->_ruleFactory->create();

                    // Validate rule data
                    if (($validateResult = $rule->validateData(new \Magento\Framework\DataObject($ruleData))) !== true) {
                        return $this->_yotpoHelper->jsonEncode($validateResult);
                    }

                    $rule->loadPost($ruleData);
                    $rule->save();
                    $rule = $this->_ruleFactory->create()->load($rule->getId());
                } catch (\Exception $e) {
                    $this->_yotpoHelper->log("[Yotpo Loyalty API - CreateCoupon - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
                    return $this->_yotpoHelper->jsonEncode([
                        "error" => 'An error has occurred trying create a new sales rule'
                    ]);
                }
            }

            // Make sure that the rule has been loaded/created
            if (!isset($rule) || !$rule || !$rule->getId()) {
                return $this->_yotpoHelper->jsonEncode([
                    "error" => 'An error has occurred trying create a new sales rule'
                ]);
            }

            // Generate coupon code for rule
            try {
                $coupon = $this->_couponFactory->create()->setData([
                    "rule_id" => $rule->getId(),
                    "code" => $code,
                    "usage_limit" => $usageLimit,
                    "is_primary" => 0,
                    "type" => \Magento\SalesRule\Api\Data\CouponInterface::TYPE_GENERATED,
                ])
                ->save();

                //Add Coupon to cart in requested
                try {
                    if ($quoteId) {
                        $quote = $this->_quoteFactory->create()->load($quoteId);
                        if ($quote->getId()) {
                            $quote->setCouponCode($code)
                                ->collectTotals()
                                ->save();
                        }
                    }
                } catch (\Exception $e) {
                    $this->_yotpoHelper->log("[Yotpo Loyalty API - CreateCoupon - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
                    return $this->_yotpoHelper->jsonEncode([
                        "error" => 'An error has occurred trying to add coupon to cart'
                    ]);
                }

                //Send Response
                return $this->_yotpoHelper->jsonEncode($coupon->getData());
                //=========================================================//
            } catch (\Exception $e) {
                $this->_yotpoHelper->log("[Yotpo Loyalty API - CreateCoupon - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
                return $this->_yotpoHelper->jsonEncode([
                    "error" => 'An error has occurred trying create a new coupon'
                ]);
            }
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("[Yotpo Loyalty API - CreateCoupon - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            return $this->_yotpoHelper->jsonEncode([
                "error" => 'An error has occurred trying create a new coupon'
            ]);
        }
        return $this->_yotpoHelper->jsonEncode([]);
    }
}
