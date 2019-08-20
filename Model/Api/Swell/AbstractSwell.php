<?php

namespace Yotpo\Loyalty\Model\Api\Swell;

class AbstractSwell
{

    /**
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @var \Yotpo\Loyalty\Helper\Schema
     */
    protected $_yotpoSchemaHelper;

    protected $_storesId;

    /**
     * @param \Yotpo\Loyalty\Helper\Data   $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoSchemaHelper = $yotpoSchemaHelper;
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
