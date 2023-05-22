<?php

namespace Yotpo\Loyalty\Block;

use Yotpo\Loyalty\Block\AbstractBlock;
use Yotpo\Loyalty\Helper\Data as YotpoLoyaltyHelper;

class UrlCouponCodeJs extends AbstractBlock
{
    /**
     * @method getUrlCouponCode
     * @return string|null
     */
    public function getUrlCouponCode()
    {
        return (string) $this->getRequest()->getParam(YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM);
    }

    /**
     * @method getUrlCouponCodeUrl
     * @return string
     */
    public function getUrlCouponCodeUrl()
    {
        if(!($couponCode = $this->getUrlCouponCode())){
            return '';
        }
        return $this->getUrl('yotpo_loyalty/session/urlcouponcode') . '?' . http_build_query([
            YotpoLoyaltyHelper::COUPON_CODE_QUERY_PARAM => $couponCode
        ]);
    }
}
