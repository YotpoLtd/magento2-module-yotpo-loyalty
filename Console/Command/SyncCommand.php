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
 * Yotpo - Manual sync
 */
class SyncCommand extends Command
{

    /**#@+
     * Keys and shortcuts for input arguments and options
     */
    const FORCE = 'force';
    const LIMIT = 'limit';
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
        $this->setName('yotpo:loyalty:sync')
            ->setDescription('Sync Yotpo queued items manually')
            ->setDefinition([
                new InputOption(
                    self::LIMIT,
                    '-l',
                    InputOption::VALUE_OPTIONAL,
                    'Max entity items to sync (WARNING: If you set this too high, it might be too heavy for your server, make sure it can handle it first)',
                    null
                ),
                new InputOption(
                    self::FORCE,
                    '-f',
                    InputOption::VALUE_NONE,
                    'Force submitting everything, ignoring `swell_sync_max_tryouts`'
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

        $this->_registry->register('isYotpoSyncCommand', true);
        $this->updateMemoryLimit();

        try {
            $output->writeln('<info>' . 'Working on it (Imagine a spinning gif loager) ...' . '</info>');

            //================================================================//
            $this->_jobs->initConfig([
                "output" => $output,
                "limit" => $input->getOption(self::LIMIT),
                "force" => ($input->getOption(self::FORCE)) ? true : false
            ]);

            $this->_jobs->processSyncQueue();
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
