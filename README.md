# Magento 2 [Yotpo](https://www.yotpo.com/) Module

Magento 2 module for integration with Yotpo.

---

## ✓ Install via composer (recommended)
Run the following command under your Magento 2 root dir:

```
composer require yotpo/magento2-module-yotpo-loyalty
php bin/magento maintenance:enable
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento maintenance:disable
php bin/magento cache:flush
```

## Install manually under app/code
1. Download & place the contents of this repository under {YOUR-MAGENTO2-ROOT-DIR}/app/code/Yotpo/Loyalty  
2. Run the following commands under your Magento 2 root dir:
```
php bin/magento maintenance:enable
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento maintenance:disable
php bin/magento cache:flush
```

---

https://www.yotpo.com/

Copyright © 2018 Yotpo. All rights reserved.  

![Yotpo Logo](https://loyalty-app.yotpo.com/assets/loyalty-sidebar.svg)
