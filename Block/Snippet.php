<?php

namespace Yotpo\Loyalty\Block;

use Yotpo\Loyalty\Block\AbstractBlock;

class Snippet extends AbstractBlock
{
    public function getFullActionName()
    {
        return $this->getRequest()->getFullActionName();
    }

    public function getUseYotpoJsSdk()
    {
        return $this->_yotpoHelper->getUseYotpoJsSdk();
    }

    public function getLoadYotpoSnippet()
    {
        return $this->_yotpoHelper->getLoadYotpoSnippet();
    }

    /**
     * Return true/false if the current page in the cart page
     * @method shouldLoadSnippet
     * @return bool
     */
    public function isCartPage()
    {
        return $this->getFullActionName() === $this->_yotpoHelper->getCartPageFullActionName();
    }

    /**
     * Return true/false if the current page in the checkout page
     * @method shouldLoadSnippet
     * @return bool
     */
    public function isCheckoutPage()
    {
        return $this->getFullActionName() === $this->_yotpoHelper->getCheckoutPageFullActionName();
    }

    public function isPathMatchingSnippetPatterns()
    {
        $matching = false;

        try {
            $uri = $this->getRequest()->getRequestUri();
            foreach ($this->_yotpoHelper->getLoadYotpoSnippetPathPatternsArray() as $pattern) {
                if (\preg_match($pattern, $uri)) {
                    $matching = true;
                    break;
                }
            }
        } catch (\Exception $e) {
            $this->_yotpoHelper->log("[Yotpo - isPathMatchingSnippetPatterns - ERROR] " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }

        return $matching;
    }

    /**
     * Return true/false if the snippet should be loaded on the current page.
     * @method shouldLoadSnippet
     * @return bool  (Default: true)
     */
    public function shouldLoadSnippet()
    {
        if (!$this->isEnabled()) {
            return false;
        }

        // If using Yotpo JS SDK - always load the snippet.
        if ($this->getUseYotpoJsSdk()) {
            return true;
        }

        // If not using Yotpo JS SDK - load the snippet by configuration.
        switch ($this->getLoadYotpoSnippet()) {
            case 'checkout':
                return $this->isCheckoutPage();
                break;

            case 'checkout_cart':
                return $this->isCartPage() || $this->isCheckoutPage();
                break;

            case 'url_path_patterns':
                return $this->isPathMatchingSnippetPatterns();
                break;

            case 'all':
            default:
                return true;
                break;
        }

        return true;
    }
}
