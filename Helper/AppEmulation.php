<?php

namespace Yotpo\Loyalty\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;

class AppEmulation extends AbstractHelper
{
    /**
     * @var Emulation
     */
    private $appEmulation;

    /**
     * @method __construct
     * @param  Context                  $context
     * @param  Emulation                $appEmulation
     */
    public function __construct(
        Context $context,
        Emulation $appEmulation
    ) {
        parent::__construct($context);
        $this->appEmulation = $appEmulation;
    }

    public function getAppEmulation()
    {
        return $this->appEmulation;
    }

    /**
     * Start environment emulation of the specified store
     *
     * Function returns information about initial store environment and emulates environment of another store
     *
     * @param integer $storeId
     * @param string  $area
     * @param bool    $force A true value will ensure that environment is always emulated, regardless of current store
     * @return $this
     */
    public function startEnvironmentEmulation($storeId, $area = Area::AREA_FRONTEND, $force = false)
    {
        $this->getAppEmulation()->startEnvironmentEmulation($storeId, $area, $force);
        return $this;
    }

    /**
     * Stop environment emulation
     *
     * Function restores initial store environment
     *
     * @return $this
     */
    public function stopEnvironmentEmulation()
    {
        $this->getAppEmulation()->stopEnvironmentEmulation();
        return $this;
    }

    public function emulateFrontendArea($storeId, $force = true)
    {
        $this->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, $force);
        return $this;
    }

    public function emulateAdminArea($storeId = null, $force = true)
    {
        $storeId = ($storeId === null) ? $this->getDefaultStoreId() : $storeId;
        $this->startEnvironmentEmulation($storeId, Area::AREA_ADMINHTML, $force);
        return $this;
    }

    //=====================================================================================================//
}
