<?php

namespace Yotpo\Loyalty\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Widget\Button;

class DownloadDebugItems extends AbstractField
{
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'Yotpo_Loyalty::system/config/advance/download_debug_items.phtml';

    /**
     * @return string
     */
    public function getDownloadLogFileButtonHtml()
    {
        $button = $this->getLayout()
            ->createBlock(Button::class)
            ->setData(
                [
                    'id' => 'yotpo_loyalty_download_log_file_button',
                    'label' => __('Download Debug Log')
                ]
            );

        if (version_compare($this->_yotpoHelper->getMagentoVersion(), '2.3.5', '<')) {
            // Add session param on Magento versions prior to 2.3.5
            $url = $this->_urlBuilder->addSessionParam()->getUrl('yotpo_loyalty/system/downloadDebugItem/type/log_file');
        } else {
            $url = $this->_urlBuilder->getUrl('yotpo_loyalty/system/downloadDebugItem/type/log_file');
        }

        $button->setOnClick("window.open('{$url}','_blank');");

        return $button->toHtml();
    }

    /**
     * @return string
     */
    public function getDownloadDebugInfoButtonHtml()
    {
        $button = $this->getLayout()
            ->createBlock(Button::class)
            ->setData(
                [
                    'id' => 'yotpo_loyalty_download_debug_info_button',
                    'label' => __('Download Debug Info')
                ]
            );

        if (version_compare($this->_yotpoHelper->getMagentoVersion(), '2.3.5', '<')) {
            // Add session param on Magento versions prior to 2.3.5
            $url = $this->_urlBuilder->addSessionParam()->getUrl('yotpo_loyalty/system/downloadDebugItem/type/debug_info');
        } else {
            $url = $this->_urlBuilder->getUrl('yotpo_loyalty/system/downloadDebugItem/type/debug_info');
        }

        $button->setOnClick("window.open('{$url}','_blank');");

        return $button->toHtml();
    }
}
