<?php

if (PHP_SAPI == 'cli') {
    \Magento\Framework\Console\CommandLocator::register('Yotpo\Loyalty\Console\CommandList');
}
