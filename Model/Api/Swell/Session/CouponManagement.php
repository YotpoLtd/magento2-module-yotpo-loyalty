<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Session;

class CouponManagement implements \Yotpo\Loyalty\Api\Swell\Session\CouponManagementInterface
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
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoSchemaHelper = $yotpoSchemaHelper;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * {@inheritdoc}
     */
    public function postCoupon()
    {
        try {
            if (!$this->_yotpoHelper->isEnabled()) {
                throw new \Exception('The Yotpo Loyalty module has been disabled from store configuration.');
            }
            $quote = $this->_checkoutSession->getQuote();
            if ($quote->getId()) {
                $code = (string) $this->_yotpoHelper->getRequest()->getParam('coupon_code');
                $codesToRemove = $this->_yotpoHelper->getRequest()->getParam('swell_coupon_codes');
                $existingCodes = $quote->getData("coupon_code");
                $couponCodes = [$code];

                if (isset($codesToRemove) && isset($existingCodes)) {
                    $codesToRemove = (is_array($codesToRemove)) ? $codesToRemove : explode(",", strtoupper((string)$codesToRemove));
                    $existingCodes = explode(",", strtoupper($existingCodes));
                    foreach ($existingCodes as $existingCode) {
                        if (!in_array($existingCode, $codesToRemove)) {
                            $couponCodes[] = $existingCode;
                        }
                    }
                }
                $couponCode = implode(",", $couponCodes);
                $quote->setCouponCode($couponCode)->setTotalsCollectedFlag(false)->collectTotals()->save();
            }
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("[Yotpo Loyalty API - Coupon - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            return $this->_yotpoHelper->jsonEncode([
                "error" => true
            ]);
        }

        return $this->_yotpoHelper->jsonEncode([
            "success" => true
        ]);
    }
}
