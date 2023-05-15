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

    /**
     * @var \Yotpo\Loyalty\Helper\AppEmulation
     */
    protected $_appEmulationHelper;

    protected $_storesId;

    /**
     * @param \Yotpo\Loyalty\Helper\Data  $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoSchemaHelper = $yotpoSchemaHelper;
        $this->_appEmulationHelper = $appEmulationHelper;
    }

    /**
     * Check if request is authorized
     * @return bool
     */
    protected function isAuthorized()
    {
        if (($this->_storesId = $this->_yotpoHelper->getStoreIdBySwellApiKey($this->_yotpoHelper->getRequest()->getParam("shared_secret")))) {
            $this->_appEmulationHelper->emulateFrontendArea($this->_storesId);
            return true;
        }
        return false;
    }
}
