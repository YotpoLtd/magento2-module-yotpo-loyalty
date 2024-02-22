<?php

namespace Yotpo\Loyalty\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Yotpo Loyalty LoadJsSnippet source model.
 *
 * @category Loyalty
 * @package  Loyalty_Loyalty
 */
class LoadJsSnippet implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'all', 'label' => __('On All Pages')],
            ['value' => 'checkout', 'label' => __('Only on Checkout')],
            ['value' => 'checkout_cart', 'label' => __('Only on Checkout & Cart')],
            ['value' => 'url_path_patterns', 'label' => __('Only on Specified Paths (Using Regex)')],
        ];
    }

    public function toArray()
    {
        return [
            'all' => __('On All Pages'),
            'checkout' => __('Only on Checkout'),
            'checkout_cart' => __('Only on Checkout & Cart'),
            'url_path_patterns' => __('Only on Specified Paths (Using Regex)'),
        ];
    }
}
