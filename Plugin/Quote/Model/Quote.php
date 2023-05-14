<?php

namespace Yotpo\Loyalty\Plugin\Quote\Model;

use Magento\Quote\Model\Quote as QuoteModel;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Yotpo\Loyalty\Helper\Data as YotpoLoyaltyHelper;

/**
 * Class Quote
 */
class Quote
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var YotpoLoyaltyHelper
     */
    private $yotpoHelper;

    /**
     * @method __construct
     * @param  CheckoutSession    $checkoutSession
     * @param  CustomerSession    $customerSession
     * @param  YotpoLoyaltyHelper $yotpoHelper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        YotpoLoyaltyHelper $yotpoHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->yotpoHelper = $yotpoHelper;
    }

    /**
     * @method beforeSetCouponCode
     * @param  QuoteModel         $quote
     * @param  string             $value (Coupon code)
     */
    public function beforeSetCouponCode(QuoteModel $quote, $value)
    {
        if ($this->yotpoHelper->isEnabled()) {
            try {
                if (
                    !empty($value) &&
                    ($couponCode = $this->customerSession->getData(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM)) &&
                    $value !== $couponCode
                ) {
                    $this->customerSession->unsetData(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM);
                }
            } catch (\Exception $e) {
                $this->yotpoHelper->log("[Yotpo - Quote::beforeSetCouponCode - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            }
        }
    }

    /**
     * @method beforeCollectTotals
     * @param  QuoteModel         $quote
     */
    public function beforeCollectTotals(QuoteModel $quote)
    {
        if ($this->yotpoHelper->isEnabled()) {
            try {
                if (
                    ($couponCode = $this->customerSession->getData(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM)) &&
                    $quote && $quote->getId() &&
                    $quote->getItemsCount() > 0 &&
                    $quote->getCouponCode() !== $couponCode
                ) {
                    $quote->setCouponCode($couponCode)->setTotalsCollectedFlag(false)->save();
                }
            } catch (\Exception $e) {
                $this->yotpoHelper->log("[Yotpo - Quote::beforeCollectTotals - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            }
        }
    }

    /**
     * @method afterCollectTotals
     * @param  QuoteModel         $quote
     * @param  QuoteModel         $quoteModel
     */
    public function afterCollectTotals(QuoteModel $quote, QuoteModel $quoteModel)
    {
        if ($this->yotpoHelper->isEnabled()) {
            try {
                if (
                    ($couponCode = $this->customerSession->getData(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM)) &&
                    $quoteModel->getItemsCount() > 0 &&
                    $quoteModel->getCouponCode() === $couponCode
                ) {
                    $this->customerSession->unsetData(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM);
                }
            } catch (\Exception $e) {
                $this->yotpoHelper->log("[Yotpo - Quote::afterCollectTotals - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            }
        }
        return $quoteModel;
    }
}
