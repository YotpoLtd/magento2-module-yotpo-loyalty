<?php

namespace Yotpo\Loyalty\Model\ResourceModel\Queue;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'queue_id';
    protected $_eventPrefix = 'yotpo_loyalty_queue_collection';
    protected $_eventObject = 'queue_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Yotpo\Loyalty\Model\Queue::class, 
            \Yotpo\Loyalty\Model\ResourceModel\Queue::class
        );
    }
}
