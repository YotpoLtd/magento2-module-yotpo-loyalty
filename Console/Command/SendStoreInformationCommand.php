<?php

namespace Yotpo\Loyalty\Console\Command;

use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Yotpo - Send Store Information Webhook
 */
class SendStoreInformationCommand extends Command
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
     * @var \Yotpo\Loyalty\Helper\Data
     */
    protected $_yotpoHelper;

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
        $this->setName('yotpo:loyalty:send-store-information')
            ->setDescription('Send store information webhooks to Yotpo')
            ->setDefinition([
                new InputOption(
                    self::FORCE,
                    '-f',
                    InputOption::VALUE_NONE,
                    'Force submission, ignoring `store_information_webhhoks_enabled`'
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
        $this->_yotpoHelper = $this->_objectManager->get(\Yotpo\Loyalty\Helper\Data::class);

        try {
            $output->writeln('<info>' . 'Working on it (Imagine a spinning gif loager) ...' . '</info>');

            if (!$this->_yotpoHelper->isStoreInformationWebhhoksEnabled() && !$input->getOption(self::FORCE)) {
                $output->writeln('Store information webhooks have been disabled from the module config. In order to run this command, please re-enable or try again with -f (force).');
                return 0;
            }
            //================================================================//
            $this->_jobs->initConfig([
                "output" => $output,
                "force" => ($input->getOption(self::FORCE)) ? true : false
            ]);

            $this->_jobs->sendStoreInformationWebhooks();
            //================================================================//

            $output->writeln('<info>' . 'Done :)' . '</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return 1;
        }

        return 0;
    }
}
