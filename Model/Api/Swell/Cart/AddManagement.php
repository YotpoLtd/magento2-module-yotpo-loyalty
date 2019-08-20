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
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     */
    public function __construct(
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Helper\Schema $yotpoSchemaHelper,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Customer\Model\CustomerFactory $customerFactory
    ) {
        $this->_quoteFactory = $quoteFactory;
        $this->_quoteRepository = $quoteRepository;
        $this->_productRepository = $productRepository;
        $this->_customerFactory = $customerFactory;
        parent::__construct($yotpoHelper, $yotpoSchemaHelper);
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

            $request = new \Magento\Framework\DataObject([
                'product' => $product->getId(),
                'qty' => $qty,
                'custom_price' => $price,
                'original_custom_price' => $price,
                'swell_redemption_id' => $redemptionId,
                'swell_points_used' => $pointsUsed
            ]);
            $quoteItem = $quote->addProduct($product, $request);
            $quoteItem
                ->setCustomPrice($price)
                ->setOriginalCustomPrice($price)
                ->setSwellRedemptionId($redemptionId)
                ->setSwellPointsUsed($pointsUsed)
                ->save();

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
