<?php

namespace Yotpo\Loyalty\Block\Adminhtml\System\Config;

use Yotpo\Loyalty\Helper\AbstractApi;

class MerchantInfoConfirm extends AbstractField
{
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'Yotpo_Loyalty::system/config/advance/merchant_info_confirm.phtml';

    /**
     * Get merchant_info API URL
     * @method getMerchantInfoApiUrl
     * @return string
     */
    public function getMerchantInfoApiUrl()
    {
        return AbstractApi::SWELL_API_PRODUCTION_URL . 'merchant_info';
    }
}
