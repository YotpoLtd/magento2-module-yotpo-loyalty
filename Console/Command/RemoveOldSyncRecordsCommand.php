<?php

namespace Yotpo\Loyalty\Console\Command;

use Composer\Console\ApplicationFactory;
use Magento\Deploy\Model\Filesystem;
use Magento\Framework\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInputFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yotpo\Loyalty\Helper\Data as YotpoHelper;

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
     * @param \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @param \Yotpo\Loyalty\Cron\Jobs
     */
    protected $_jobs;

    /**
     * @method __construct
     * @param Filesystem $filesystem
     * @param ArrayInputFactory $arrayInputFactory
     * @param ApplicationFactory $applicationFactory
     * @param Registry $registry
     * @param YotpoHelper $yotpoHelper
     */
    public function __construct(
        Filesystem $filesystem,
        ArrayInputFactory $arrayInputFactory,
        ApplicationFactory $applicationFactory,
        Registry $registry,
        YotpoHelper $yotpoHelper
    ) {
        $this->_filesystem = $filesystem;
        $this->_arrayInputFactory = $arrayInputFactory;
        $this->_applicationFactory = $applicationFactory;
        $this->_registry = $registry;
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

        $this->_jobs = $this->_yotpoHelper->getObjectManager()->get('\Yotpo\Loyalty\Cron\Jobs');

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
