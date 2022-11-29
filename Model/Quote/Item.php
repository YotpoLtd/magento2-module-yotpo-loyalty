<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Yotpo\Loyalty\Model\Quote;

class Item extends \Magento\Quote\Model\Quote\Item implements \Magento\Quote\Api\Data\CartItemInterface
{
    /**
     * Checking item data
     *
     * @return $this
     */
    public function checkData()
    {
        $yotpoHelper = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Yotpo\Loyalty\Helper\Data::class);

        $this->setHasError(false);
        $this->clearMessage();
        $qty = $this->_getData('qty');

        try {
            $this->setQty($qty);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->setHasError(true);
            $this->setMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->setHasError(true);
            $yotpoHelper->log("[Yotpo - AbstractItem - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            $this->setMessage(__('Item qty declaration error: ' . $e->getMessage() . json_encode($e->getTraceAsString())));
        }

        try {
            $this->getProduct()->getTypeInstance()->checkProductBuyState($this->getProduct());
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->setHasError(true)->setMessage($e->getMessage());
            $this->getQuote()->setHasError(
                true
            )->addMessage(
                __('Some of the products below do not have all the required options.')
            );
        } catch (\Exception $e) {
            $this->setHasError(true)->setMessage(__('Something went wrong during the item options declaration.'));
            $this->getQuote()->setHasError(true)->addMessage(__('We found an item options declaration error.'));
        }

        if ($this->getProduct()->getHasError()) {
            $this->setHasError(true)->setMessage(__('Some of the selected options are not currently available.'));
            $this->getQuote()->setHasError(true)->addMessage($this->getProduct()->getMessage(), 'options');
        }

        if ($this->getHasConfigurationUnavailableError()) {
            $this->setHasError(
                true
            )->setMessage(
                __('Selected option(s) or their combination is not currently available.')
            );
            $this->getQuote()->setHasError(
                true
            )->addMessage(
                __('Some item options or their combination are not currently available.'),
                'unavailable-configuration'
            );
            $this->unsHasConfigurationUnavailableError();
        }

        return $this;
    }
}
