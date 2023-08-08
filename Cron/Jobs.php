<?php

namespace Yotpo\Loyalty\Cron;

use Magento\Store\Model\ScopeInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Jobs
{
    private $_limit = null;

    private $_force = false;

    /**
     * @var Symfony\Component\Console\Output\OutputInterface
     */
    protected $_output;

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
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\Registry $registry
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     * @param \Yotpo\Loyalty\Model\QueueFactory $yotpoQueueFactory
     * @param \Yotpo\Loyalty\Helper\ApiRequest $apiRequestHelper
     * @param \Magento\Framework\Notification\NotifierInterface $notifierPool
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \Magento\Framework\Registry $registry,
        \Yotpo\Loyalty\Helper\Data $yotpoHelper,
        \Yotpo\Loyalty\Model\QueueFactory $yotpoQueueFactory,
        \Yotpo\Loyalty\Helper\ApiRequest $apiRequestHelper,
        \Magento\Framework\Notification\NotifierInterface $notifierPool
    ) {
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
        if ($this->_yotpoQueueCollection === null) {
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
                $this->{$method}($val);
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
        if ($this->_output instanceof OutputInterface) {
            //Output to terminal
            $this->_output->writeln('<' . $type . '>' . json_encode($message) . '</' . $type . '>');
            if ($data) {
                $this->_output->writeln('<comment>' . json_encode($data) . '</comment>');
            }
        }

        //Log to var/log/yotpo_loyalty.log
        $this->_yotpoHelper->log($message, $type, $data);

        return $this;
    }

    protected function addAdminNotification(string $title, $description = "", $type = 'critical')
    {
        $method = 'add' . ucfirst($type);
        $this->_notifierPool->{$method}($title, $description);
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
        $limit = ($this->_limit !== null) ? (int)$this->_limit : $this->_swellSyncLimit;
        $this->_processOutput("Limit is set to: " . (($limit>0) ? $limit : '0 (no-limit)'), 'comment');
        if ($limit) {
            $collection->setPageSize($limit);
        }
        return $collection;
    }

    /**
     * @method _setCollectionLimit
     * @param  \Yotpo\Loyalty\Model\Queue $queueItem
     * @return object|null
     */
    protected function getPreparedSchemaWithCredentials(\Yotpo\Loyalty\Model\Queue $queueItem)
    {
        $preparedSchema = $queueItem->getPreparedSchema();
        if ($preparedSchema && is_object($preparedSchema)) {
            $preparedSchema->api_key = $this->_yotpoHelper->getSwellApiKey(ScopeInterface::SCOPE_STORE, $queueItem->getStoreId());
            $preparedSchema->guid = $this->_yotpoHelper->getSwellGuid(ScopeInterface::SCOPE_STORE, $queueItem->getStoreId());
        }
        return $preparedSchema;
    }

    /**
     * Process Sync Queue.
     * @method syncOrdersPrepare
     * @return $this
     */
    public function processSyncQueue()
    {
        try {
            $this->_processOutput("Jobs::processSyncQueue() - [STARTED]", "info");
            $this->setCrontabAreaCode();

            $collection = $this->getYotpoQueueCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter('sent', 0)
                ->addFieldToFilter('store_id', ['in' => $this->_yotpoHelper->getEnabledStoreIds()]);
            $this->_setCollectionTryoutsFilter($collection);
            $collection->getSelect()->order(['created_at ASC']);
            $this->_setCollectionLimit($collection);

            $collectionCount = $collection->count();
            $this->_processOutput("Found {$collectionCount} queued items (on stores: " . implode(',', $this->_yotpoHelper->getEnabledStoreIds()) . ").", 'comment');

            $addAdminNotifications = "";
            $i = 0;
            foreach ($collection as $item) {
                $i++;
                $this->_processOutput('== Processing ID: ' . $item->getId() . ' (' . $i . '/' . $collectionCount . ') ...', 'comment');

                try {
                    $this->_processOutput('*** Entity Type: ' . $item->getEntityType() . ' | Entity ID: ' . $item->getEntityId() . ' | Entity Status: ' . $item->getEntityStatus() . ' | Store ID: ' . $item->getStoreId() . ' | Try: ' . ($item->getTryouts()+1), 'comment');
                    $response = $this->_apiRequestHelper->webhooksRequest($this->getPreparedSchemaWithCredentials($item));
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
                $addAdminNotifications = "*If you enabled debug mode Yotpo - Loyalty Settings, you should see the details in the log file (var/log/yotpo_loyalty.log)" . $addAdminNotifications;
                $this->addAdminNotification("Yopto - An error occurred during the automated sync process! (module: Yotpo_Loyalty)", $addAdminNotifications, 'critical');
            }

            $this->_processOutput("Jobs::processSyncQueue() - [DONE]", "info");
        } catch (\Exception $e) {
            $this->_processOutput('Jobs::processSyncQueue() - [ERROR] ' . $e->getMessage(), 'error', [$e]);
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
            $this->_processOutput("Jobs::removeOldSyncRecords() - [STARTED]", "info");

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

            $this->_processOutput("Jobs::removeOldSyncRecords() - [DONE]", "info");
        } catch (\Exception $e) {
            $this->_processOutput('[ERROR] ' . $e->getMessage(), 'error', [$e]);
            $this->addAdminNotification("Yopto - An error occurred during the automated removeOldSyncRecords process!", "*If you enabled debug mode Yotpo - Loyalty Settings, you should see the details in the log file (var/log/yotpo_loyalty.log)", 'critical');
        }

        return $this;
    }

    /////////////
    // Helpers //
    /////////////

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
