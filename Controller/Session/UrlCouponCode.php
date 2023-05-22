<?php

namespace Yotpo\Loyalty\Controller\Session;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Yotpo\Loyalty\Helper\Data as YotpoLoyaltyHelper;

/**
 * /swell/session/urlcouponcode?yotpo_loyalty_copon_code={COUPON_CODE}
 * Use AJAX call from the frontend to make sure that the url-coupon is processed or stored on the session.
 */
class UrlCouponCode extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

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
     * @param  Context           $context
     * @param  JsonFactory       $resultJsonFactory
     * @param  YotpoHelper       $yotpoHelper
     * @param  YotpoSchemaHelper $yotpoSchemaHelper
     * @param  CheckoutSession   $checkoutSession
     * @param  MessageManager    $messageManager
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Http $request,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        YotpoLoyaltyHelper $yotpoHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->yotpoHelper = $yotpoHelper;
    }
    /**
     * @return ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        if ($this->yotpoHelper->isEnabled()) {
            try {
                if(
                    ($couponCode = trim((string)$this->request->getParam(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM))) &&
                    !$this->customerSession->getData(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM)
                ){
                    $this->customerSession->setData(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM, $couponCode);
                    if (($quote = $this->checkoutSession->getQuote())) {
                        $quote->setCouponCode($couponCode)->setTotalsCollectedFlag(false)->collectTotals()->save();
                    }
                }
                return $result->setData([
                    'coupon_code' => $couponCode,
                    'success' => (string)$this->customerSession->getData(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM) === $couponCode,
                ]);
            } catch (\Exception $e) {
                $this->yotpoHelper->log("[Yotpo - UrlCouponCodeJs - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
                return $result->setData([
                    'coupon_code' => $couponCode,
                    'error' => true,
                ]);
            }
        }

        return $result->setHttpResponseCode(403)->setData(['error' => true, 'message' => 'Access Denied!']);
    }
}
