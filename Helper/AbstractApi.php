<?php

namespace Yotpo\Loyalty\Helper;

abstract class AbstractApi extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SWELL_API_PRODUCTION_URL = "https://loyalty.yotpo.com/magento/";

    /**
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @var \Yotpo\Loyalty\Lib\Http\Client\Curl
     */
    protected $_curl;

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    protected $_cacheTypeList;

    /**
     * @var int
     */
    protected $_status;

    /**
     * @var array
     */
    protected $_headers;

    /**
     * @var array
     */
    protected $_body;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Lib\Http\Client\Curl $curl
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Lib\Http\Client\Curl $curl,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
    ) {
        parent::__construct($context);
        $this->_yotpoHelper = $yotpoHelper;
        $this->_curl = $curl;
        $this->_cacheTypeList = $cacheTypeList;
    }

    /**
     *
     * @return string
     */
    protected function getSwellApiUrl()
    {
        //Switch here between Sandbox/Production environment if needed
        return self::SWELL_API_PRODUCTION_URL;
    }

    /**
     * @method sendCurlRequest
     * @param  string          $apiEndpointPath
     * @param  mixed           $params
     * @param  string          $method ("POST"/"GET"/...)
     * @param  string          $contentType
     * @return $this
     */
    protected function sendSwellRequest(string $apiEndpointPath, $params, string $method = "POST", string $contentType = 'application/json')
    {
        $this->_yotpoHelper->log("[Yotpo - AbstractApi::sendSwellRequest() - request]", "info", ["url" => $this->getSwellApiUrl() . $apiEndpointPath, "params" => $params, "method" => $method, "contentType" => $contentType]);

        $this->_status = $this->_headers = $this->_body = null;
        $this->_curl->setHeaders([
            'Content-Type' => $contentType
        ]);
        $this->_curl->{strtolower($method)}(
            $this->getSwellApiUrl() . $apiEndpointPath,
            $params
        );

        $this->_yotpoHelper->log("[Yotpo - AbstractApi::sendSwellRequest() - response]", "info", $this->prepareCurlResponseData());

        return $this;
    }

    /**
     * @return int
     */
    protected function getCurlStatus()
    {
        if ($this->_status === null) {
            $this->_status = $this->_curl->getStatus();
        }

        return $this->_status;
    }

    /**
     * @return array
     */
    protected function getCurlHeaders()
    {
        if ($this->_headers === null) {
            $this->_headers = $this->_curl->getHeaders();
        }

        return $this->_headers;
    }

    /**
     * @return array
     */
    protected function getCurlBody()
    {
        if ($this->_body === null) {
            $this->_body = json_decode($this->_curl->getBody());
        }

        return $this->_body;
    }

    /**
     * @return array
     */
    protected function prepareCurlResponseData()
    {
        return [
            'status' => $this->getCurlStatus(),
            'headers' => $this->getCurlHeaders(),
            'body' => $this->getCurlBody(),
        ];
    }

    public function cleanConfigCache()
    {
        $this->_cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
        $this->_cacheTypeList->cleanType(\Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER);
        return $this;
    }

    protected function isOkResponse()
    {
        return (($body = (array)$this->getCurlBody()) && isset($body['status']) && strtolower($body['status']) === 'ok') ? true : false;
    }
}
