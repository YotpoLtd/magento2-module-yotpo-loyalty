<?php

namespace Yotpo\Loyalty\Plugin\Quote;

use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item as QuoteItem;

/**
 * Class Item
 */
class Item
{
    /**
     * Check product representation in item
     *
     * @param   QuoteItem     $item (subject)
     * @param   bool          $result
     * @param   Product       $product
     * @return  bool
     */
    public function afterRepresentProduct(QuoteItem $item, $result, Product $product)
    {
        if ($result && $item->getSwellAddedItem() && !($item->getCustomPrice()*1)) {
            $result = false;
        }
        return $result;
    }
}
