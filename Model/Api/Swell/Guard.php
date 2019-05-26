<?php

namespace Yotpo\Loyalty\Model\Api\Swell;

class Guard
{

    /**
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    protected $_storesId;

    /**
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper
    ) {
        $this->_yotpoHelper = $yotpoHelper;

        if (!$this->_yotpoHelper->isEnabled()) {
            header('HTTP/1.0 403 Forbidden');
            return $this->_yotpoHelper->jsonEncode([
                "error" => 1,
                "message" => "Access Denied!"
            ]);
        }

        if (!$this->isAuthorized()) {
            header('HTTP/1.0 401 Unauthorized');
            return $this->_yotpoHelper->jsonEncode([
                "error" => 1,
                "message" => "Access Denied!"
            ]);
        }
    }

    /**
     * Check if request is authorized
     * @return bool
     */
    protected function isAuthorized()
    {
        if (($this->_storesId = $this->_yotpoHelper->getStoreIdBySwellApiKey($this->_yotpoHelper->getRequest()->getParam("shared_secret")))) {
            $this->_yotpoHelper->startEnvironmentEmulation($this->_storesId, \Magento\Framework\App\Area::AREA_FRONTEND, true);
            return true;
        }
        return false;
    }
}
