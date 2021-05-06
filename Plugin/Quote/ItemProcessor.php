<?php

namespace Yotpo\Loyalty\Plugin\Quote;

use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Processor;

/**
 * Class ItemProcessor
 */
class ItemProcessor
{
    public function beforePrepare(Processor $subject, Item $item, DataObject $request, Product $candidate)
    {
        if ($item->getSwellAddedItem() && !($item->getCustomPrice()*1)) {
            $item->setData(CartItemInterface::KEY_QTY, 0);
            $request->setQty(1);
            $candidate->setCartQty(1);
        }
        return [$item, $request, $candidate];
    }
}
