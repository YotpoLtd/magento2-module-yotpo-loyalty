<?php

namespace Yotpo\Loyalty\Block;

class Snippet extends \Yotpo\Loyalty\Block\AbstractBlock
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
    ) {
        parent::__construct($context, $yotpoHelper);
        $this->storeManager = $storeManager;
    }

    /**
     * @return string
     */
    public function getSwellSessionSnippetApiUrl()
    {
        return $this->storeManager->getStore()->getUrl('rest/V1/swell/session/snippet');
    }
}
