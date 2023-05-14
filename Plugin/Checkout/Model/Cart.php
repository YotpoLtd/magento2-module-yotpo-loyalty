<?php

namespace Yotpo\Loyalty\Plugin\Checkout\Model;

use Magento\Checkout\Model\Cart as CheckoutCartModel;
use Yotpo\Loyalty\Helper\Data as YotpoLoyaltyHelper;

/**
 * Class Cart
 */
class Cart
{
    /**
     * @var YotpoLoyaltyHelper
     */
    private $yotpoHelper;

    /**
     * @method __construct
     * @param  YotpoLoyaltyHelper $yotpoHelper
     */
    public function __construct(
        YotpoLoyaltyHelper $yotpoHelper
    ) {
        $this->yotpoHelper = $yotpoHelper;
    }

    /**
     * @method beforeUpdateItems
     * @param  CheckoutCartModel $subject
     * @param  array             $data
     * @return array
     */
    public function beforeUpdateItems(CheckoutCartModel $subject, $data)
    {
        if ($this->yotpoHelper->isEnabled()) {
            try {
                foreach ($data as $itemId => &$itemInfo) {
                    $item = $subject->getQuote()->getItemById($itemId);
                    if (!$item) {
                        continue;
                    }

                    if ($item->getSwellAddedItem() && !($item->getCustomPrice()*1) && (isset($itemInfo['qty']) && $itemInfo['qty'] > 1)) {
                        $itemInfo['qty'] = 1;
                    }
                }
            } catch (\Exception $e) {
                $this->yotpoHelper->log("[Yotpo - Cart::beforeUpdateItems - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            }
        }
        return [$data];
    }
}
