<?php

namespace Yotpo\Loyalty\Console\Command;

use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Command\Command;
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
    public const FORCE = 'force';
    public const LIMIT = 'limit';
    /**#@- */

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Yotpo\Loyalty\Cron\Jobs
     */
    protected $_jobs;

    /**
     * @method __construct
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->_objectManager = $objectManager;
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
        $this->_jobs = $this->_objectManager->get(\Yotpo\Loyalty\Cron\Jobs::class);

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
            return 1;
        }

        return 0;
    }
}
