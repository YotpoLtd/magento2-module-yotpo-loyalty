<?php

namespace Yotpo\Loyalty\Block;

use Yotpo\Loyalty\Block\AbstractBlock;

class CheckoutWidget extends AbstractBlock
{
    /**
     * @return mixed
     */
    public function getSwellInstanceId()
    {
        return $this->_yotpoHelper->getSwellInstanceId();
    }
}
