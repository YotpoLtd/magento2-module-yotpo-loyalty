<?php

namespace Yotpo\Loyalty\Plugin\Quote;

/**
 * Class ItemToOrderItem
 */
class ItemToOrderItem
{
    public function aroundConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $additional = []
    ) {
        /** @var $orderItem Item */
        $orderItem = $proceed($item, $additional);
        $orderItem->setData('swell_redemption_id', $item->getData('swell_redemption_id'));
        $orderItem->setData('swell_points_used', $item->getData('swell_points_used'));
        $orderItem->setData('swell_added_item', $item->getData('swell_added_item'));
        return $orderItem;
    }
}
