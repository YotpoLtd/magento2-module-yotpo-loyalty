<?php

namespace Yotpo\Loyalty\Model;

class Queue extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'yotpo_loyalty_queue';

    protected $_cacheTag = 'yotpo_loyalty_queue';

    protected $_eventPrefix = 'yotpo_loyalty_queue';

    protected function _construct()
    {
        $this->_init(\Yotpo\Loyalty\Model\ResourceModel\Queue::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }

    public function setPreparedSchema($schema)
    {
        if (is_array($schema) || is_object($schema)) {
            return $this->setData('prepared_schema', json_encode($schema));
        }
    }

    public function getPreparedSchema()
    {
        $schema = $this->getData('prepared_schema');
        if (!(is_array($schema) || is_object($schema))) {
            $schema = json_decode($schema);
        }
        return $schema;
    }

    public function setResponse($response)
    {
        if (is_array($response) || is_object($response)) {
            return $this->setData('response', json_encode($response));
        }
    }

    public function getResponse()
    {
        $response = $this->getData('response');
        if (!(is_array($response) || is_object($response))) {
            $response = json_decode($response);
        }
        return $response;
    }
}
