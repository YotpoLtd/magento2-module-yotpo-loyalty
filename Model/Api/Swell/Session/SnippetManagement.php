<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Session;

class SnippetManagement implements \Yotpo\Loyalty\Api\Swell\Session\SnippetManagementInterface
{
    /**
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\Customer\Model\GroupFactory
     */
    protected $_customerGroupFactory;

    protected $_customer;
    protected $_customerGroupCode;

    /**
     * @method __construct
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\GroupFactory
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\GroupFactory $customerGroupFactory
    ) {
        $this->_yotpoHelper = $yotpoHelper;
        $this->_customerSession = $customerSession;
        $this->_customerFactory = $customerFactory;
        $this->_customerGroupFactory = $customerGroupFactory;
    }

    protected function isEnabled()
    {
        return $this->_yotpoHelper->isEnabled();
    }

    public function getSwellGuid()
    {
        return $this->_yotpoHelper->getSwellGuid();
    }

    public function getSwellApiKey()
    {
        return $this->_yotpoHelper->getSwellApiKey();
    }

    public function getUseYotpoJsSdk()
    {
        return $this->_yotpoHelper->getUseYotpoJsSdk();
    }

    protected function isCustomerLoggedIn()
    {
        return $this->_customerSession->isLoggedIn();
    }

    protected function isForceCartReload()
    {
        return (is_object($this->getCustomer()) && $this->getCustomer()->getData('yotpo_force_cart_reload')) ? true : false;
    }

    protected function getCustomer()
    {
        if ($this->_customer === null) {
            if ($this->isCustomerLoggedIn()) {
                $this->_customer = $this->_customerFactory->create()->load($this->_customerSession->getCustomer()->getId());
            }
        }
        return $this->_customer;
    }

    protected function getCustomerGroupCode()
    {
        if ($this->_customerGroupCode === null) {
            if (is_object($this->getCustomer())) {
                $customerGroup = $this->_customerGroupFactory->create()->load($this->getCustomer()->getGroupId());
                if ($customerGroup && $customerGroup->getCode()) {
                    $this->_customerGroupCode = $customerGroup->getCode();
                } else {
                    $this->_customerGroupCode = false;
                }
            } else {
                $this->_customerGroupCode = false;
            }
        }
        return $this->_customerGroupCode;
    }

    protected function setForceCartReload($value)
    {
        if ($this->_customer) {
            $value = ((int)$value > 0) ? 1 : 0;
            $this->_customer->setData('yotpo_force_cart_reload', $value);
            $customerData = $this->_customer->getDataModel();
            $customerData->setData('yotpo_force_cart_reload', $value);
            $this->_customer->updateData($customerData);
            $this->_customer->save();
        }
    }

    protected function getCustomerIdentificationData()
    {
        if (!$this->isCustomerLoggedIn()) {
            return false;
        }
        $customer = $this->getCustomer();
        $data = new \Magento\Framework\DataObject();
        if (is_object($customer) && $customer->getId()) {
            $data->setData([
                "id" => $customer->getId(),
                "email" => $customer->getEmail()
            ]);
            if (($groupCode = $this->getCustomerGroupCode())) {
                $data->setGroupCode($groupCode);
            }
        }
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getSnippet()
    {
        $response = [
            "error" => 0,
            "snippet" => "",
        ];
        try {
            if ($this->isEnabled()) {
                if ($this->isForceCartReload()) {
                    $this->setForceCartReload(0);
                    $response["snippet"] .= '
                        <!-- Yotpo Loyalty - Reload customerData cart -->
                        <script>
                            (function  () {
                                require([
                                    "Magento_Customer/js/customer-data"
                                ],function(customerData) {
                                    customerData.invalidate(["cart"]);
                                });
                            })();
                        </script>
                        <!--/ Yotpo Loyalty - Reload customerData cart -->
                    ';
                }
                if (($swellGuid = $this->getSwellGuid()) && ($swellApiKey = $this->getSwellApiKey())) {
                    $response["snippet"] .= '
                        <!-- Yotpo Loyalty - Swell Snippet -->
                        <div id="swell-customer-identification" style="display:none !important;"
                    ';
                    if (($identificationData = $this->getCustomerIdentificationData())) {
                        $response["snippet"] .= '
                            data-authenticated="true"
                            data-email="' . $identificationData->getEmail() . '"
                            data-id="' . $identificationData->getId() . '"
                            data-token="' . hash('sha256', $identificationData->getEmail() . $swellApiKey) . '"
                        ';
                        if (($groupCode = $identificationData->getGroupCode())) {
                            $response["snippet"] .= 'data-tags="[&#34;' . $groupCode . '&#34;]"';
                        }
                    }

                    $response["snippet"] .= '
                        ></div>
                    ';

                    if ($this->getUseYotpoJsSdk()) {
                        $response["snippet"] .= '
                            <script type="text/javascript" async src="https://cdn-loyalty.yotpo.com/loader/' . $swellGuid . '.js"></script>
                        ';
                    }

                    $response["snippet"] .= '
                        <!--/ Yotpo Loyalty - Swell Snippet -->
                    ';
                }
            }
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("[Yotpo Loyalty API - Savecart - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            if ($this->_yotpoHelper->isDebugMode()) {
                $response = [
                    "error" => 1,
                    "message" => $e->getMessage(),
                ];
            }
        }

        return $this->_yotpoHelper->jsonEncode($response);
    }
}
