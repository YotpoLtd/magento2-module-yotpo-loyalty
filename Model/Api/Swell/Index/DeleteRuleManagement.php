<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Index;

use Yotpo\Loyalty\Model\Api\Swell\AbstractSwell;

class DeleteRuleManagement extends AbstractSwell implements \Yotpo\Loyalty\Api\Swell\Index\DeleteRuleManagementInterface
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
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $_ruleFactory;

    /**
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory
    ) {
        $this->_ruleFactory = $ruleFactory;
        parent::__construct($yotpoHelper, $yotpoSchemaHelper, $appEmulationHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function postDeleteRule()
    {
        if (!$this->isAuthorized()) {
            return $this->_yotpoHelper->jsonEncode([
                "error" => 1,
                "message" => "Access Denied!"
            ]);
        }
        try {
            $response = [
                "success" => true,
                "message" => "Success",
                "data" => [],
            ];

            //Extract Request Params:
            $ruleIds = $this->_yotpoHelper->getRequest()->getParam('id', '');
            if (!(is_array($ruleIds) || is_object($ruleIds))) {
                $ruleIds = explode(",", $ruleIds);
            }
            $ruleIds = array_filter(array_map('trim', (array) $ruleIds));
            //===========================================================================================//

            if (!$ruleIds) {
                return $this->_yotpoHelper->jsonEncode([
                    "error" => "`id` is a required field!"
                ]);
            }

            foreach ($ruleIds as $ruleId) {
                try {
                    $rule = $this->_ruleFactory->create()->load($ruleId);
                    if ($rule->getId() && $rule->getRuleId()) {
                        $rule->delete();
                        $response["data"][$ruleId] = [
                            "success" => true
                        ];
                    } else {
                        $response["data"][$ruleId] = [
                            "success" => true,
                            "message" => 'There is no rule with this id'
                        ];
                    }
                } catch (\Exception $e) {
                    $this->_yotpoHelper->log("[Yotpo Loyalty API - DeleteRule - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
                    $response["data"][$ruleId] = [
                        "error" => 'An error has occurred while trying to delete rule ID' . $ruleId
                    ];
                    $response["success"] = false;
                    $response["error"] = true;
                    $response["message"] = "Has errors";
                }
            }
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("[Yotpo Loyalty API - DeleteRule - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            return $this->_yotpoHelper->jsonEncode([
                "error" => 'An error has occurred while trying to delete rule(s)' . $ruleId,
                "data" => $ruleIds
            ]);
        }

        return $this->_yotpoHelper->jsonEncode($response);
    }
}
