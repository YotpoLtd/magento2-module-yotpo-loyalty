<?php

namespace Yotpo\Loyalty\Helper;

class ApiRequest extends AbstractApi
{
    public function setupRequest($scopeId = null, $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        $this->sendSwellRequest(
            "setup",
            [
                "api_key" => $this->_yotpoHelper->getSwellApiKey($scope, $scopeId),
                "guid" => $this->_yotpoHelper->getSwellGuid($scope, $scopeId),
                "root_api_url" => rtrim($this->_yotpoHelper->getBaseUrl($scope, $scopeId), "/") . "/rest/V1",
                "version" => $this->_yotpoHelper->getMagentoVersion(),
                "currency" => $this->_yotpoHelper->getDefaultCurrency($scope, $scopeId),
                "id" => $this->_yotpoHelper->getDomain($scope, $scopeId),
                "website" => $this->_yotpoHelper->getBaseUrl($scope, $scopeId),
                "delete_used_coupons_after_days" => $this->_yotpoHelper->getDeleteUsedCoupons($scope, $scopeId),
            ]
        );

        $processedResponse = new \Magento\Framework\DataObject();
        if ($this->isOkResponse()) {
            $processedResponse->setData([
                "error" => 0,
                "message" => __('Your Magento instance has successfully been connected to your Swell account.'),
                "response" => $this->prepareCurlResponseData()
            ]);
        } else {
            $response = $this->getCurlBody();
            if (is_object($response) && isset($response->error_message) && $response->error_message) {
                $processedResponse->setData([
                    "error" => 1,
                    "message" => __($response->error_message),
                    "response" => $this->prepareCurlResponseData()
                ]);
            } else {
                $processedResponse->setData([
                    "error" => 1,
                    "message" => __("API-Key validation failed for unknown reason!"),
                    "response" => $this->prepareCurlResponseData()
                ]);
            }
        }

        $this->_yotpoHelper->log("[Yotpo - ApiRequest::setupRequest() - processed response]", ($processedResponse->getError() ? "error" : "info"), $processedResponse->getData());

        return $processedResponse;
    }

    public function webhooksRequest($params = [])
    {
        $this->sendSwellRequest("webhooks", (array)$params);

        $processedResponse = new \Magento\Framework\DataObject();
        if ($this->isOkResponse()) {
            $processedResponse->setData([
                "error" => 0,
                "message" => __('Webhook successfully sent!'),
                "response" => $this->prepareCurlResponseData()
            ]);
        } else {
            $response = $this->getCurlBody();
            if (is_object($response) && isset($response->error_message) && $response->error_message) {
                $processedResponse->setData([
                    "error" => 1,
                    "message" => __($response->error_message),
                    "response" => $this->prepareCurlResponseData()
                ]);
            } else {
                $processedResponse->setData([
                    "error" => 1,
                    "message" => __("Webhook request failed for unknown reason!"),
                    "response" => $this->prepareCurlResponseData()
                ]);
            }
        }

        $this->_yotpoHelper->log("[Yotpo - ApiRequest::webhooksRequest() - processed response]", ($processedResponse->getError() ? "error" : "info"), $processedResponse->getData());

        return $processedResponse;
    }
}
