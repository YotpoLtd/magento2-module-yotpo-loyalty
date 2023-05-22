<?php

namespace Yotpo\Loyalty\Model\Api\Swell\Cart;

use Yotpo\Loyalty\Model\Api\Swell\AbstractSwell;

class AddManagement extends AbstractSwell implements \Yotpo\Loyalty\Api\Swell\Cart\AddManagementInterface
{
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $_quoteRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var Magento\Quote\Model\Quote\ItemFactory
     */
    protected $_itemFactory;

    /**
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Quote\Model\Quote\ItemFactory $itemFactory
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Yotpo\Loyalty\Helper\AppEmulation $appEmulationHelper,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Quote\Model\Quote\ItemFactory $itemFactory
    ) {
        $this->_quoteFactory = $quoteFactory;
        $this->_quoteRepository = $quoteRepository;
        $this->_productRepository = $productRepository;
        $this->_customerFactory = $customerFactory;
        $this->_itemFactory = $itemFactory;
        parent::__construct($yotpoHelper, $yotpoSchemaHelper, $appEmulationHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdd()
    {
        if (!$this->isAuthorized()) {
            return $this->_yotpoHelper->jsonEncode([
                "error" => 1,
                "message" => "Access Denied!"
            ]);
        }
        try {
            //Extract Request Params:
            $quoteId = intval($this->_yotpoHelper->getRequest()->getParam('quote_id'));
            $sku = $this->_yotpoHelper->getRequest()->getParam('sku');
            $price = floatval($this->_yotpoHelper->getRequest()->getParam('price'));
            $qty = floatval($this->_yotpoHelper->getRequest()->getParam('qty'));
            $redemptionId = intval($this->_yotpoHelper->getRequest()->getParam('redemption_id'));
            $pointsUsed = intval($this->_yotpoHelper->getRequest()->getParam('points_used'));
            //================================================================//

            if (!is_numeric($price)) {
                $price = 0.0;
            }
            if (!is_numeric($qty)) {
                $qty = 1;
            }

            $quote = $this->_quoteFactory->create()->load($quoteId);
            if (!$quote->getId()) {
                return $this->_yotpoHelper->jsonEncode([
                    "error" => 'There is no quote with this quote_id'
                ]);
            }
            $couponCode = $quote->getCouponCode();
            if ($couponCode) {
                $quote->setCouponCode("")->setTotalsCollectedFlag(false)->collectTotals()->save()->load($quoteId);
            }
            $product = $this->_productRepository->get($sku);
            if (!$product->getId()) {
                return $this->_yotpoHelper->jsonEncode([
                    "error" => 'There is no product with this SKU'
                ]);
            }

            $quoteItem = $this->_itemFactory->create();
            $quoteItem
                ->setCustomPrice($price)
                ->setOriginalCustomPrice($price)
                ->setSwellRedemptionId($redemptionId)
                ->setSwellPointsUsed($pointsUsed)
                ->setSwellAddedItem(1)
                ->setQty(1)
                ->setProduct($product);

            $quote->addItem($quoteItem);
            $quote->save();
            $quote->setTotalsCollectedFlag(false)->collectTotals()->save();

            if ($quote->getCustomerId()) {
                $customer = $this->_customerFactory->create()->load($quote->getCustomerId());
                if ($customer->getId()) {
                    $customer->setData('yotpo_force_cart_reload', 1);
                    $customerData = $customer->getDataModel();
                    $customerData->setData('yotpo_force_cart_reload', 1);
                    $customer->updateData($customerData);
                    $customer->save();
                }
            }

            if ($couponCode) {
                try {
                    $quote->setCouponCode($couponCode)->setTotalsCollectedFlag(false)->collectTotals()->save()->load($quoteId);
                } catch (\Exception $e) {
                    return $this->_yotpoHelper->jsonEncode([
                        "success" => true,
                        "message" => "[Yotpo Loyalty API - Add(ToCart) - WARNING] " . $e->getMessage()
                    ]);
                }
            }

            return $this->_yotpoHelper->jsonEncode([
                "success" => true
            ]);
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("[Yotpo Loyalty API - Add(ToCart) - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
            return $this->_yotpoHelper->jsonEncode([
                "error" => 'An error has occurred trying to add item to cart'
            ]);
        }
        return $this->_yotpoHelper->jsonEncode([]);
    }
}
