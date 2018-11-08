<?php

namespace Yotpo\Loyalty\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Yotpo Loyalty KeepQueue source model.
 *
 * @category Loyalty
 * @package  Loyalty_Loyalty
 */
class KeepQueue implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '1_day', 'label' => __('1 Day')],
            ['value' => '1_week', 'label' => __('1 Week')],
            ['value' => '1_month', 'label' => __('1 Month')],
            ['value' => '1_year', 'label' => __('1 Year')],
            ['value' => 'forever', 'label' => __('Forever')],
        ];
    }

    public function toArray()
    {
        return [
            '1_day' => __('1 Day'),
            '1_week' => __('1 Week'),
            '1_month' => __('1 Month'),
            '1_year' => __('1 Month'),
            'forever' => __('Forever'),
        ];
    }
}
