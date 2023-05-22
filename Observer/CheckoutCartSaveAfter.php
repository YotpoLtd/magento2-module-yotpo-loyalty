<?php

namespace Yotpo\Loyalty\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\Http;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Yotpo\Loyalty\Helper\Data as YotpoLoyaltyHelper;

/**
 * CheckoutCartSaveAfter
 */
class CheckoutCartSaveAfter implements ObserverInterface
{
    /**
     * @var Http
     */
    private $request;

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
     * @param  Http               $request
     * @param  CheckoutSession    $checkoutSession
     * @param  CustomerSession    $customerSession
     * @param  YotpoLoyaltyHelper $yotpoHelper
     */
    public function __construct(
        Http $request,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        YotpoLoyaltyHelper $yotpoHelper
    ) {
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->yotpoHelper = $yotpoHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($this->yotpoHelper->isEnabled()) {
            try {
                $couponCode = trim((string)$this->customerSession->getData(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM));
                $quote = $observer->getEvent()->getCart()->getQuote();
                if (
                    $couponCode &&
                    $quote &&
                    $quote->getId() &&
                    $quote->getItemsCount() > 0 &&
                    $quote->getCouponCode() === $couponCode
                ) {
                    $this->customerSession->unsetData(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM);
                    $this->yotpoHelper->log("[CheckoutCartSaveAfter] COUPON ADDED AUTOMATICALLY" . $couponCode, "debug", [
                        "quote_id" => $quote->getId(),
                        "coupon_code" => $couponCode,
                    ]);
                }
            } catch (\Exception $e) {
                $this->yotpoHelper->log("[CheckoutCartSaveAfter - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            }
        }
    }
}
