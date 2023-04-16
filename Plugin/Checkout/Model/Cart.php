<?php

namespace Yotpo\Loyalty\Plugin\Checkout\Model;

use Magento\Checkout\Model\Cart as CheckoutCartModel;

/**
 * Class Cart
 */
class Cart
{
    public function beforeUpdateItems(CheckoutCartModel $subject, $data)
    {
        foreach ($data as $itemId => &$itemInfo) {
            $item = $subject->getQuote()->getItemById($itemId);
            if (!$item) {
                continue;
            }

            if ($item->getSwellAddedItem() && !($item->getCustomPrice()*1) && (isset($itemInfo['qty']) && $itemInfo['qty'] > 1)) {
                $itemInfo['qty'] = 1;
            }
        }
        return [$data];
    }
}
