<?php

namespace Yotpo\Loyalty\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\Http;
use Yotpo\Loyalty\Helper\Data as YotpoLoyaltyHelper;

/**
 * CustomerSessionInit
 */
class CustomerSessionInit implements ObserverInterface
{
    /**
     * @var Http
     */
    private $request;

    /**
     * @var YotpoLoyaltyHelper
     */
    private $yotpoHelper;

    /**
     * @method __construct
     * @param  Http               $request
     * @param  YotpoLoyaltyHelper $yotpoHelper
     */
    public function __construct(
        Http $request,
        YotpoLoyaltyHelper $yotpoHelper
    ) {
        $this->request = $request;
        $this->yotpoHelper = $yotpoHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        if ($this->yotpoHelper->isEnabled()) {
            try {
                if (($couponCode = $this->request->getParam(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM))) {
                    $customerSession = $observer->getEvent()->getCustomerSession();
                    $customerSession->setData(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM, $couponCode);
                }
            } catch (\Exception $e) {
                $this->yotpoHelper->log("[CustomerSessionInit - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            }
        }
    }
}
