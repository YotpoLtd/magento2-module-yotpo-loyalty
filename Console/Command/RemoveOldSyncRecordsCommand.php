<?php

namespace Yotpo\Loyalty\Console\Command;

use Composer\Console\ApplicationFactory;
use Magento\Deploy\Model\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInputFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Yotpo - Remove Old Sync Records
 */
class RemoveOldSyncRecordsCommand extends Command
{

    /**#@+
     * Keys and shortcuts for input arguments and options
     */
    const FORCE = 'force';
    /**#@- */

    /**
     * @var Magento\Deploy\Model\Filesystem
     */
    private $_filesystem;

    /**
     * @var ArrayInputFactory
     * @deprecated
     */
    private $_arrayInputFactory;

    /**
     * @var ApplicationFactory
     */
    private $_applicationFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @param \Yotpo\Loyalty\Cron\Jobs
     */
    protected $_jobs;

    /**
     * @param \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @method __construct
     * @param Filesystem $filesystem
     * @param ArrayInputFactory $arrayInputFactory
     * @param ApplicationFactory $applicationFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Yotpo\Loyalty\Cron\Jobs $jobs
     * @param \Yotpo\Loyalty\Helper\Data $yotpoHelper
     */
    public function __construct(
        Filesystem\Proxy $filesystem,
        ArrayInputFactory\Proxy $arrayInputFactory,
        ApplicationFactory\Proxy $applicationFactory,
        \Magento\Framework\Registry\Proxy $registry,
        \Yotpo\Loyalty\Cron\Jobs\Proxy $jobs,
        \Yotpo\Loyalty\Helper\Data\Proxy $yotpoHelper
    ) {
        $this->_filesystem = $filesystem;
        $this->_arrayInputFactory = $arrayInputFactory;
        $this->_applicationFactory = $applicationFactory;
        $this->_registry = $registry;
        $this->_jobs = $jobs;
        $this->_yotpoHelper = $yotpoHelper;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('yotpo:loyalty:remove-old-sync-records')
            ->setDescription('Remove old Yotpo sent/failed items from yotpo_sync_queue table manually')
            ->setDefinition([
                new InputOption(
                    self::FORCE,
                    '-f',
                    InputOption::VALUE_NONE,
                    'Force deleting everything, ignoring `keep_yotpo_sync_queue`'
                ),
            ]);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->_yotpoHelper->isEnabled()) {
            $output->writeln('<error>' . 'The Yotpo Loyalty module has been disabled from system configuration. Please enable it in order to run this command!' . '</error>');
            return;
        }

        $this->_registry->register('isRemoveOldSyncRecordsCommand', true);
        $this->updateMemoryLimit();

        try {
            $output->writeln('<info>' . 'Working on it (Imagine a spinning gif loager) ...' . '</info>');

            //================================================================//
            $this->_jobs->initConfig([
                "output" => $output,
                "force" => ($input->getOption(self::FORCE)) ? true : false
            ]);

            $this->_jobs->removeOldSyncRecords();
            //================================================================//

            $output->writeln('<info>' . 'Done :)' . '</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }

    /**
     * @return void
     */
    private function updateMemoryLimit()
    {
        if (function_exists('ini_set')) {
            @ini_set('display_errors', 1);
            @ini_set('memory_limit', '2048M');
        }
    }
}
