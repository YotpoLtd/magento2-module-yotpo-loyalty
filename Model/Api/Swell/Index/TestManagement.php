<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

use Yotpo\Loyalty\Model\Api\Swell\AbstractSwell;

class TestManagement extends AbstractSwell implements \Yotpo\Loyalty\Api\Swell\Index\TestManagementInterface
{
    /**
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper
    ) {
        parent::__construct($yotpoHelper, $yotpoSchemaHelper, $appEmulationHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function getSuccess()
    {
        if (!$this->isAuthorized()) {
            return $this->_yotpoHelper->jsonEncode([
                "error" => 1,
                "message" => "Access Denied!"
            ]);
        }
        return $this->_yotpoHelper->jsonEncode([
            "success" => true
        ]);
    }
}
