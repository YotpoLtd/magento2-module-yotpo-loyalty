<?php

namespace Yotpo\Loyalty\Cron;

use Symfony\Component\Console\Output\OutputInterface;

class Jobs
{
    private $_limit = null;

    private $_force = false;

    private $_memoryLimitUpdated = false;

    /**
     * @var Symfony\Component\Console\Output\OutputInterface
     */
    protected $_output;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $_appState;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @var \Yotpo\Loyalty\Model\QueueFactory
     */
    protected $_yotpoQueueFactory;

    /**
     * @var \Yotpo\Loyalty\Model\ResourceModel\Queue\Collection
     */
    protected $_yotpoQueueCollection;

    /**
     * @var \Yotpo\Loyalty\Helper\ApiRequest
     */
    protected $_apiRequestHelper;

    /**
     * @var \Magento\Framework\Notification\NotifierInterface
     */
    protected $_notifierPool;

    /**
     * System config (defaults):
     */
    protected $_swellSyncLimit = 10;
    protected $_swellSyncMaxTryouts = 5;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\Registry $registry
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Model\QueueFactory $yotpoQueueFactory
     * @param \Yotpo\Loyalty\Helper\ApiRequest $apiRequestHelper
     * @param \Magento\Framework\Notification\NotifierInterface $notifierPool
    */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\Registry $registry,
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Model\QueueFactory $yotpoQueueFactory,
        \Yotpo\Loyalty\Helper\ApiRequest $apiRequestHelper,
        \Magento\Framework\Notification\NotifierInterface $notifierPool
    ) {
        $this->_logger = $logger;
        $this->_appState = $appState;
        $this->_registry = $registry;
        $this->_yotpoHelper = $yotpoHelper;
        $this->_yotpoQueueFactory = $yotpoQueueFactory;
        $this->_apiRequestHelper = $apiRequestHelper;
        $this->_notifierPool = $notifierPool;

        $this->_swellSyncLimit = $this->_yotpoHelper->getSwellSyncLimit();
        $this->_swellSyncMaxTryouts = $this->_yotpoHelper->getSwellSyncMaxTryouts();
    }

    ////////////////////////////////
    // Config / Setters / Getters //
    ////////////////////////////////

    public function getYotpoQueueCollection()
    {
        if (is_null($this->_yotpoQueueCollection)) {
            $this->_yotpoQueueCollection = $this->_yotpoQueueFactory->create()->getCollection();
        }
        return $this->_yotpoQueueCollection;
    }

    /**
     * @method initConfig
     * @param array $config
     * @return $this
     */
    public function initConfig(array $config)
    {
        foreach ($config as $key => $val) {
            $method = $this->_yotpoHelper->strToCamelCase(strtolower($key), 'set');
            if (method_exists($this, $method)) {
                call_user_func([$this, $method], $val);
            }
        }
        return $this;
    }

    /**
     * @method setOutput
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->_output = $output;
        return $this;
    }

    /**
     * @method getOutput
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->_output;
    }

    /**
     * @method setLimit
     * @param null|int $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->_limit = $limit;
        return $this;
    }

    /**
     * @method getLimit
     * @return null|int
     */
    public function getLimit()
    {
        return $this->_limit;
    }

    /**
     * @method setForce
     * @param bool $force
     * @return $this
     */
    public function setForce(bool $force)
    {
        $this->_force = $force;
        return $this;
    }

    /**
     * @method getForce
     * @return bool
     */
    public function getForce()
    {
        return $this->_force;
    }

    /////////////////////////////
    // Processes & Validations //
    /////////////////////////////

    /**
     * Process output messages (log to system.log / output to terminal)
     * @method _processOutput
     * @return $this
     */
    protected function _processOutput($message, $type = "info", $data = [])
    {
        if ($this->_output instanceof OutputInterface) { //Output to terminal
            $this->_output->writeln('<' . $type . '>' . print_r($message, true) . '</' . $type . '>');
            if ($data) {
                $this->_output->writeln('<comment>' . print_r($data, true) . '</comment>');
            }
        } elseif ($this->_yotpoHelper->isDebugMode()) { //Log to system.log
            switch ($type) {
                case 'error':
                    $this->_logger->error(print_r($message, true), $data);
                break;
                default:
                    $this->_logger->info(print_r($message, true), $data);
                break;
            }
        }
        return $this;
    }

    protected function addAdminNotification(string $title, $description = "", $type = 'critical')
    {
        call_user_func_array([$this->_notifierPool, 'add' . ucfirst($type)], [$title, $description]);
        return $this;
    }

    //////////
    // Jobs //
    //////////

    public function ordersSync()
    {
        $this->syncOrdersPrepare()->submit();
    }

    public function customersSync()
    {
        $this->syncCustomersPrepare()->submit();
    }

    /////////////////////////
    // Preparations & Sync //
    /////////////////////////

    /**
     * @method _setCollectionTryoutsFilter
     * @param  \Magento\Framework\Data\Collection   $collection
     * @return \Magento\Framework\Data\Collection
     */
    protected function _setCollectionTryoutsFilter(\Magento\Framework\Data\Collection $collection)
    {
        if (!$this->_force) {
            if ($this->_swellSyncMaxTryouts > 0) {
                $collection->addFieldToFilter('tryouts', ['lt' => $this->_swellSyncMaxTryouts]);
            } else {
                $this->_processOutput("Max-tryouts is set to 0 (no-limit)! Ignoring tryouts count", 'comment');
            }
        } else {
            $this->_processOutput("Force option is enabled! Ignoring tryouts count & max-tryouts configuration", 'comment');
        }
        return $collection;
    }

    /**
     * @method _setCollectionLimit
     * @param  \Magento\Framework\Data\Collection   $collection
     * @return \Magento\Framework\Data\Collection
     */
    protected function _setCollectionLimit(\Magento\Framework\Data\Collection $collection)
    {
        $limit = (!is_null($this->_limit)) ? (int)$this->_limit : $this->_swellSyncLimit;
        $this->_processOutput("Limit is set to: " . (($limit>0) ? $limit : '0 (no-limit)'), 'comment');
        if ($limit) {
            $collection->setPageSize($limit);
        }
        return $collection;
    }

    /**
     * Process Sync Queue.
     * @method syncOrdersPrepare
     * @return $this
     */
    public function processSyncQueue()
    {
        try {
            if ($this->_yotpoHelper->isEnabled()) {
                $this->updateMemoryLimit();
                $this->setCrontabAreaCode();

                $collection = $this->getYotpoQueueCollection()->addFieldToSelect('*')->addFieldToFilter('sent', 0);

                $this->_setCollectionTryoutsFilter($collection);
                $collection->setOrder('created_at', 'asc');
                $this->_setCollectionLimit($collection);

                $collectionCount = $collection->count();
                $this->_processOutput("Found {$collectionCount} queued items.", 'comment');

                $addAdminNotifications = "";
                $i = 0;
                foreach ($collection as $item) {
                    $i++;
                    $this->_processOutput('== Processing ID: ' . $item->getId() . ' (' . $i . '/' . $collectionCount . ') ...', 'comment');

                    try {
                        $this->_processOutput('*** Entity Type: ' . $item->getEntityType() . "\n" . '*** Entity ID: ' . $item->getEntityId() . "\n" . '*** Entity Status: ' . $item->getEntityStatus() . "\n" . '*** Store ID: ' . $item->getStoreId(), 'comment');
                        $response = $this->_apiRequestHelper->webhooksRequest($item->getPreparedSchema());
                        $item->setResponse($response->getResponse())->setTryouts($item->getTryouts()+1);
                        if ($response->getError()) {
                            $this->_processOutput('== [ERROR] ' . $response->getMessage(), 'error', [$response]);
                            $item->setHasErrors(1);
                            if ($item->getTryouts() >= $this->_swellSyncMaxTryouts) {
                                $addAdminNotifications .= ' | Queued Item ID: ' . $item->getId() . ', Entity Type: ' . $item->getEntityType() . ', Entity ID: ' . $item->getEntityId() . ', Entity Status: ' . $item->getEntityStatus() . ', Store ID: ' . $item->getStoreId();
                            }
                        } else {
                            $this->_processOutput('== [SUCCESS] ' . $response->getMessage(), 'comment');
                            $item->setHasErrors(0)->setSent(1)->setSentAt($this->_yotpoHelper->getCurrentDate());
                        }
                        $item->save();
                    } catch (\Exception $e) {
                        $this->_processOutput('== [ERROR] ' . $e->getMessage(), 'error', [$e]);
                    }

                    $this->_processOutput('== Moving forward...', 'comment');
                }

                if ($addAdminNotifications) {
                    $addAdminNotifications = "*If you enabled debug mode Yotpo - Loyalty Settings, you should see the details in the log file (var/log/system.log)" . $addAdminNotifications;
                    $this->addAdminNotification("Yopto - An error occurred during the automated sync process!", $addAdminNotifications, 'critical');
                }

                $this->_processOutput("[processSyncQueue - DONE]");
            }
        } catch (\Exception $e) {
            $this->_processOutput('[ERROR] ' . $e->getMessage(), 'error', [$e]);
        }

        return $this;
    }

    /**
     * Remove Old Sent/Failed Sync Records.
     * @method removeOldSyncRecords
     * @return $this
     */
    public function removeOldSyncRecords()
    {
        try {
            if ($this->_force) {
                $this->_processOutput("Force option is enabled! Ignoring `keep_yotpo_sync_queue`.", 'comment');
            }
            if (($keep = $this->_yotpoHelper->getKeepYotpoSyncQueue()) !== 'forever' || $this->_force) {
                if (!$this->_force) {
                    switch ($keep) {
                        case '1_day':
                            $interval = "-1 day";
                        break;
                        case '1_week':
                            $interval = "-1 week";
                        break;
                        case '1_month':
                            $interval = "-1 month";
                        break;
                        case '1_year':
                            $interval = "-1 year";
                        break;
                        default:
                            return $this;
                        break;
                    }
                }

                $collection = $this->getYotpoQueueCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter(['sent', 'tryouts'], [
                        ['eq' => 1],
                        ['gteq' => $this->_swellSyncMaxTryouts]
                    ]);
                if (!$this->_force) {
                    $collection->addFieldToFilter('created_at', ['to' => date('Y-m-d', strtotime($interval))]);
                }

                $collectionCount = $collection->count();
                $this->_processOutput("Found {$collectionCount} items to delete...", 'comment');
                if ($collectionCount) {
                    $collection->walk('delete');
                }
                $this->_processOutput("[SUCCESS]", 'comment');
            }
        } catch (\Exception $e) {
            $this->_processOutput('[ERROR] ' . $e->getMessage(), 'error', [$e]);
            $this->addAdminNotification("Yopto - An error occurred during the automated removeOldSyncRecords process!", "*If you enabled debug mode Yotpo - Loyalty Settings, you should see the details in the log file (var/log/system.log)", 'critical');
        }

        return $this;
    }

    /////////////
    // Helpers //
    /////////////

    /**
     * @return void
     */
    private function updateMemoryLimit()
    {
        if (!$this->_memoryLimitUpdated && function_exists('ini_set')) {
            @ini_set('display_errors', 1);
            @ini_set('memory_limit', '2048M');
            $this->_memoryLimitUpdated = true;
        }
    }

    private function setAdminAreaCode()
    {
        try {
            $this->_appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
            $this->_registry->register('isSecureArea', true);
        } catch (\Exception $e) {
        }
        return $this;
    }

    private function setCrontabAreaCode()
    {
        try {
            $this->_appState->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);
        } catch (\Exception $e) {
        }
        return $this;
    }
}
