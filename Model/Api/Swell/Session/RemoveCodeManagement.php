<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Session;

class RemoveCodeManagement implements \Yotpo\Loyalty\Api\Swell\Session\RemoveCodeManagementInterface
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
    public function postRemoveCode()
    {
        try {
            if (!$this->_yotpoHelper->isEnabled()) {
                throw new \Exception('The Yotpo Loyalty module has been disabled from store configuration.');
            }
            $quote = $this->_checkoutSession->getQuote();
            if ($quote->getId()) {
                $codesToRemove = $this->_yotpoHelper->getRequest()->getParam('swell_coupon_code_cancel');
                $existingCodes = $quote->getData("coupon_code");
                $couponCodes = [];
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
            $this->_yotpoHelper->log("[Yotpo Loyalty API - RemoveCode - ERROR] " . $e->getMessage() . "\n" . print_r($e, true), "error");
            return $this->_yotpoHelper->jsonEncode([
                "error" => true
            ]);
        }

        return $this->_yotpoHelper->jsonEncode([
            "success" => true
        ]);
    }
}
