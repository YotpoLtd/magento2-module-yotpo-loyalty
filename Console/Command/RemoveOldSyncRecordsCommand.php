<?php

namespace Yotpo\Loyalty\Console\Command;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Symfony\Component\Console\Command\Command;
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
    public const FORCE = 'force';
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
     * @param Registry $registry
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
        $this->_jobs = $this->_objectManager->get(\Yotpo\Loyalty\Cron\Jobs::class);

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
            return 1;
        }

        return 0;
    }
}
