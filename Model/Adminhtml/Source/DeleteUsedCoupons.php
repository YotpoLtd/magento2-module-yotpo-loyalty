<?php

namespace Yotpo\Loyalty\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Yotpo Loyalty DeleteUsedCoupons source model.
 *
 * @category Loyalty
 * @package  Loyalty_Loyalty
 */
class DeleteUsedCoupons implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('No')],
            ['value' => '7', 'label' => __('After 7 days')],
            ['value' => '14', 'label' => __('After 14 days')],
            ['value' => '30', 'label' => __('After 30 days')],
            ['value' => '90', 'label' => __('After 90 days')],
        ];
    }

    public function toArray()
    {
        return [
            '0' => __('No'),
            '7' => __('After 7 days'),
            '14' => __('After 14 days'),
            '30' => __('After 30 days'),
            '90' => __('After 90 days'),
        ];
    }
}
