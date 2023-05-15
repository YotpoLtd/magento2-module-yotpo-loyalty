<?php

namespace Yotpo\Loyalty\Helper;

class Schema extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $_addressRepository;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
    ) {
        parent::__construct($context);
        $this->_yotpoHelper = $yotpoHelper;
        $this->_addressRepository = $addressRepository;
    }

    /**
     * @method getCustomerPhone
     * @param  \Magento\Customer\Model\Customer $customer
     * @return mixed
     */
    public function getCustomerPhone(\Magento\Customer\Model\Customer $customer)
    {
        try {
            return $this->_addressRepository->getById($customer->getDefaultBilling())->getTelephone();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @method ordersSchemaPrepare
     * @param  \Magento\Sales\Model\ResourceModel\Order\Collection $order
     * @return array
     */
    public function ordersSchemaPrepare(\Magento\Sales\Model\ResourceModel\Order\Collection $orderCollection)
    {
        $return = [];
        foreach ($orderCollection as $order) {
            $return[] = $this->orderSchemaPrepare($order);
        }
        return $return;
    }

    /**
     * @method orderSchemaPrepare
     * @param  \Magento\Sales\Model\Order $order
     * @param  string $entityStatus
     * @return array
     */
    public function orderSchemaPrepare(\Magento\Sales\Model\Order $order, string $entityStatus = null)
    {
        $orderData = (array) array_filter($order->getData(), function ($v) {
            return (!(is_array($v) || is_object($v)));
        });
        // Note: "api_key" & "guid" should be added dynamically before sending.
        $orderData["entity_type"] = "order";
        if ($entityStatus !== null) {
            $orderData["entity_status"] = $entityStatus;
            $orderData["topic"] = "{$orderData['entity_type']}/{$entityStatus}";
        }

        $orderData["ip_address"] = $order->getRemoteIp();
        $orderData["user_agent"] = $order->getSwellUserAgent();

        $creditMemos = $order->getCreditmemosCollection();
        $creditMemoData = [];
        try {
            foreach ($creditMemos as $creditMemo) { //go through all the credit memos for the current order.
                $_creditMemoData = $creditMemo->getData();
                $_creditMemoData['items'] = [];
                foreach ($creditMemo->getAllItems() as $creditMemoItem) { //get all credit memo items if needed
                    $_creditMemoData['items'] = $creditMemoItem->getData();
                }
                $creditMemoData[] = $_creditMemoData;
            }
        } catch (\Exception $e) {
            //Ignore errors
        }
        $orderData["refunds"] = $creditMemoData;

        $orderData["items"] = $this->orderItemsSchemaPrepare($order);

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress->getId()) {
            $orderData["billing_address"] = (object) [
                'country_code' => $billingAddress->getCountryId(),
                'first_name' => $billingAddress->getFirstname(),
                'last_name' => $billingAddress->getLastname(),
                'address1' => $billingAddress->getStreet(1),
                'address2' => $billingAddress->getStreet(2),
                'city' => $billingAddress->getCity(),
                'phone' => $billingAddress->getTelephone(),
                'zip' => $billingAddress->getPostcode()
            ];
        }

        $shippingAddress = $order->getShippingAddress();
        if (is_object($shippingAddress) && $shippingAddress->getId()) {
            $orderData["shipping_address"] = (object) [
                'country_code' => $shippingAddress->getCountryId(),
                'first_name' => $shippingAddress->getFirstname(),
                'last_name' => $shippingAddress->getLastname(),
                'address1' => $shippingAddress->getStreet(1),
                'address2' => $shippingAddress->getStreet(2),
                'city' => $shippingAddress->getCity(),
                'phone' => $shippingAddress->getTelephone(),
                'zip' => $shippingAddress->getPostcode()
            ];
        }

        return $orderData;
    }

    /**
     * @method orderItemsSchemaPrepare
     * @param  \Magento\Sales\Model\Order $order
     * @return array
     */
    public function orderItemsSchemaPrepare(\Magento\Sales\Model\Order $order)
    {
        $return = [];
        foreach ($order->getAllItems() as $orderItem) {
            $return[] = $this->orderItemSchemaPrepare($orderItem);
        }
        return $return;
    }

    /**
     * @method orderItemSchemaPrepare
     * @param  \Magento\Sales\Model\Order\Item $orderItem
     * @return array
     */
    public function orderItemSchemaPrepare(\Magento\Sales\Model\Order\Item $orderItem)
    {
        $orderItem->setData('swell_redemption_id', (int)$orderItem->getData('swell_redemption_id'));
        $orderItem->setData('swell_points_used', (int)$orderItem->getData('swell_points_used'));
        return (array) array_filter($orderItem->getData(), function ($v) {
            return (!(is_array($v) || is_object($v)));
        });
    }

    /**
     * @method customersSchemaPrepare
     * @param  \Magento\Customer\Model\ResourceModel\Customer\Collection $customerCollection
     * @return array
     */
    public function customersSchemaPrepare(\Magento\Customer\Model\ResourceModel\Customer\Collection $customerCollection)
    {
        $return = [];
        foreach ($customerCollection as $customer) {
            $return[] = $this->customerSchemaPrepare($customer);
        }
        return $return;
    }

    /**
     * @method customerSchemaPrepare
     * @param  \Magento\Customer\Model\Customer $customer
     * @param  string                                       $entityStatus
     * @return array
     */
    public function customerSchemaPrepare(\Magento\Customer\Model\Customer $customer, string $entityStatus = "created")
    {
        return [
            // Note: "api_key" & "guid" should be added dynamically before sending.
            "entity_id" => $customer->getId(),
            "entity_type" => "customer",
            "entity_status" => $entityStatus,
            "topic" => "customer/{$entityStatus}",
            "email" => $customer->getData("email"),
            "created_at" => $customer->getData("created_at"),
            "updated_at" => $customer->getData("updated_at"),
            "first_name" => $customer->getData("firstname"),
            "last_name" => $customer->getData("lastname"),
            "group_id" => $customer->getData("group_id"),
            "id" => $customer->getId(),
            "phone" => $this->getCustomerPhone($customer),
        ];
    }

    /**
     * @method quoteSchemaPrepare
     * @param  \Magento\Quote\Model\Quote $quote
     * @return array
     */
    public function quoteSchemaPrepare(\Magento\Quote\Model\Quote $quote)
    {
        if ($quote->getId()) {
            return [
                "quoteId" => $quote->getId(),
                "coupons" => array_filter(explode(",", (string)$quote->getData("coupon_code"))),
                "items" => $this->quoteItemsSchemaPrepare($quote),
            ];
        } else {
            return [
                "items" => [],
                "quoteId" => "",
                "coupons" => []
            ];
        }
    }

    /**
     * @method quoteItemsSchemaPrepare
     * @param  \Magento\Quote\Model\Quote $quote
     * @return array
     */
    public function quoteItemsSchemaPrepare(\Magento\Quote\Model\Quote $quote)
    {
        $return = [];
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $return[] = $this->quoteItemSchemaPrepare($quoteItem);
        }
        return $return;
    }

    /**
     * @method quoteItemSchemaPrepare
     * @param  \Magento\Quote\Model\Quote\Item $quoteItem
     * @return array
     */
    public function quoteItemSchemaPrepare(\Magento\Quote\Model\Quote\Item $quoteItem)
    {
        return [
            "name" => $quoteItem->getData("name"),
            "sku" => $quoteItem->getData("sku"),
            "price" => $quoteItem->getData("price"),
            "custom_price" => $quoteItem->getData("custom_price"),
            "qty" => $quoteItem->getData("qty"),
            "swell_redemption_id" => $quoteItem->getData("swell_redemption_id"),
            "swell_points_used" => $quoteItem->getData("swell_points_used"),
        ];
    }
}
