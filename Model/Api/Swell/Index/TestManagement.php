<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

class TestManagement implements \Yotpo\Loyalty\Api\Swell\Index\TestManagementInterface
{

    /**
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @param \Yotpo\Loyalty\Model\Api\Swell\Guard $swellApiGuard
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
    */
    public function __construct(
        \Yotpo\Loyalty\Model\Api\Swell\Guard $swellApiGuard,
        \Yotpo\Loyalty\Helper\Data $yotpoHelper
    ) {
        //$swellApiGuard will be initialized from it's __construct
        $this->_yotpoHelper = $yotpoHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getSuccess()
    {
        $this->_yotpoHelper->sendApiJsonResponse([
            "success" => true
        ]);
    }
}
