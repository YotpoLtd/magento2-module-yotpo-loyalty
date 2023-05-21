<?php

namespace Yotpo\Loyalty\Controller\Adminhtml\System;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem\Driver\File;
use Yotpo\Loyalty\Helper\Data as YotpoLoyaltyHelper;
use Yotpo\Loyalty\Model\QueueFactory as YotpoQuoteFactory;

/**
 * /yotpo_loyalty/system/downloadDebugItem/type/log_file
 * /yotpo_loyalty/system/downloadDebugItem/type/debug_info
 */
class DownloadDebugItem extends Action
{
    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var File
     */
    private $driverFile;

    /**
     * @var YotpoLoyaltyHelper
     */
    private $yotpoHelper;

    /**
     * @var YotpoQuoteFactory
     */
    private $yotpoQueueFactory;

    /**
     * @method __construct
     * @param  Context            $context
     * @param  FileFactory        $fileFactory
     * @param  DirectoryList      $directoryList
     * @param  File               $driverFile
     * @param  YotpoLoyaltyHelper $yotpoHelper
     * @param  YotpoQuoteFactory  $yotpoQueueFactory
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        DirectoryList $directoryList,
        File $driverFile,
        YotpoLoyaltyHelper $yotpoHelper,
        YotpoQuoteFactory $yotpoQueueFactory
    ) {
        $this->fileFactory = $fileFactory;
        $this->directoryList = $directoryList;
        $this->driverFile = $driverFile;
        $this->yotpoHelper = $yotpoHelper;
        $this->yotpoQueueFactory = $yotpoQueueFactory;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Redirect
     */
    public function execute()
    {
        switch ($this->getRequest()->getParam('type')) {
            //== Download yotpo_loyalty.log ==//
            case 'log_file':
                $yotpoLoyaltyLogPath = $this->directoryList->getPath(DirectoryList::LOG) . DIRECTORY_SEPARATOR . 'yotpo_loyalty.log';
                if ($this->driverFile->isExists($yotpoLoyaltyLogPath)) {
                    $packageContent = [
                        'type' => 'filename',
                        'value' => $yotpoLoyaltyLogPath,
                        'rm' => false
                    ];
                    return $this->fileFactory->create(
                        'yotpo_loyalty.log',
                        $packageContent,
                        DirectoryList::VAR_DIR,
                        'application/json'
                    );
                } else {
                    return $this->getResponse()
                        ->setStatusCode(Http::STATUS_CODE_404)
                        ->setContent('Yotpo Loyalty log file (var/log/yotpo_loyalty.log) not found.');
                }

                break;

            //== Download Debug Info Package ==//
            case 'debug_info':

                $package = [];

                //Prepare system info
                $package['system_info'] = [
                    'magento_version' => $this->yotpoHelper->getMagentoVersion(true),
                    'yotpo_loyalty_module_version' => $this->yotpoHelper->getModuleVersion(),
                    'php_version' => phpversion(),
                ];

                //Prepare module configuration
                $package['module_configuration'] = $this->yotpoHelper->getAllScopesConfig()
                    ->toArray();

                //Prepare yotpo_sync_queue data
                $package['yotpo_sync_queue'] = [];
                $queueUnsentItems = $this->yotpoQueueFactory->create()->getCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter('sent', 0);
                $queueUnsentItems->getSelect()->order(['has_errors DESC', 'created_at ASC']);
                $queueUnsentItems->setPageSize(1000);
                $package['yotpo_sync_queue']['last_1000_unsent'] = $queueUnsentItems->toArray();

                //Prepare module configuration
                $queueSentItems = $this->yotpoQueueFactory->create()->getCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter('sent', 1);
                $queueSentItems->getSelect()->order(['sent_at DESC']);
                $queueSentItems->setPageSize(100);
                $package['yotpo_sync_queue']['last_100_sent'] = $queueSentItems->toArray();

                return $this->fileFactory->create(
                    sprintf('yotpo_loyalty_debug_package_%s.json', $this->yotpoHelper->getCurrentDate()),
                    [
                        'type' => 'string',
                        'value' => $this->yotpoHelper->jsonEncode($package),
                        'rm' => true
                    ],
                    DirectoryList::VAR_DIR,
                    'application/json'
                );

                break;

            //== Unsupported type - Redirect back ==//
            default:
                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                    ->setUrl($this->_redirect->getRefererUrl());
                break;
        }
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Yotpo_Loyalty::config');
    }
}
