<?php

namespace Yotpo\Loyalty\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Widget\Button;

class DirectLink extends AbstractField
{
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'Yotpo_Loyalty::system/config/advance/direct_link.phtml';

    /**
     * Generate collect button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()
            ->createBlock(Button::class)
            ->setData(
                [
                    'id' => 'swell_direct_login_link_button',
                    'label' => __('Login to Yotpo Loyalty')
                ]
            );
        if (!($guid = $this->getSwellGuid()) || !($apiKey = $this->getSwellApiKey())) {
            $button->setOnClick("window.open('https://loyalty.yotpo.com/login','_blank');");
        } else {
            $button->setOnClick("window.open('https://loyalty.yotpo.com/login/{$guid}/{$apiKey}','_blank');");
        }

        return $button->toHtml();
    }
}
