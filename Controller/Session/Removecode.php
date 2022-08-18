<?php

namespace Yotpo\Loyalty\Controller\Session;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Yotpo\Loyalty\Helper\Data as YotpoHelper;
use Yotpo\Loyalty\Helper\Schema as YotpoSchemaHelper;

/**
 * /swell/session/removecode endpoint (remove coupon code from cart & go back)
 */
class Removecode extends Action
{
    /**
     * @var YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * @var YotpoSchemaHelper
     */
    protected $_yotpoSchemaHelper;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var MessageManager
     */
    protected $_messageManager;

    /**
     * @method __construct
     * @param  Context           $context
     * @param  YotpoHelper       $yotpoHelper
     * @param  YotpoSchemaHelper $yotpoSchemaHelper
     * @param  CheckoutSession   $checkoutSession
     * @param  MessageManager    $messageManager
     */
    public function __construct(
        Context $context,
        YotpoHelper $yotpoHelper,
        YotpoSchemaHelper $yotpoSchemaHelper,
        CheckoutSession $checkoutSession,
        MessageManager $messageManager
    ) {
        parent::__construct($context);
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoSchemaHelper = $yotpoSchemaHelper;
        $this->_checkoutSession = $checkoutSession;
        $this->_messageManager = $messageManager;
    }

    /**
     * @return ResponseInterface
     */
    public function execute()
    {
        try {
            if ($this->_yotpoHelper->isEnabled()) {
                $quote = $this->_checkoutSession->getQuote();
                if ($quote->getId()) {
                    $codesToRemove = $this->getRequest()->getParam('swell_coupon_code_cancel', '');
                    $existingCodes = $quote->getData("coupon_code");
                    $couponCodes = [];
                    if (isset($codesToRemove) && isset($existingCodes)) {
                        $codesToRemove = (is_array($codesToRemove)) ? $codesToRemove : explode(",", strtoupper($codesToRemove));
                        $existingCodes = explode(",", strtoupper((string)$existingCodes));
                        foreach ($existingCodes as $existingCode) {
                            if (!in_array($existingCode, $codesToRemove)) {
                                $couponCodes[] = $existingCode;
                            }
                        }
                    }
                    $couponCode = implode(",", $couponCodes);
                    $quote->setCouponCode($couponCode)->setTotalsCollectedFlag(false)->collectTotals()->save();
                }
            } else {
                $this->_yotpoHelper->log("[Yotpo Loyalty Controller - Removecode - ERROR] The Yotpo Loyalty module has been disabled from store configuration.\n" . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            }
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("[Yotpo Loyalty Controller - Removecode - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            $this->_messageManager->addError(__("An error occurred while trying to remove a coupon from cart."));
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setUrl($this->_redirect->getRefererUrl());
    }
}
