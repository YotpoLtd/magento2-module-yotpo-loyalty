<?php

namespace Yotpo\Loyalty\Console\Command;

use Composer\Console\ApplicationFactory;
use Magento\Deploy\Model\Filesystem;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInputFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Yotpo\Loyalty\Helper\Data as YotpoHelper;

class UninstallCommand extends Command
{
    const CONFIRM_MESSAGE = "<question>Are you sure you want to uninstall Yotpo? (y/n)[n]\n*This will remove all Yotpo attributes & data from DB.\n*This process is irreversible! You should backup first.</question>\n";
    const RESET_CONFIG_CONFIRM_MESSAGE = "<question>Do you want to also remove all Yotpo configurations (reset to default)? (y/n)[n]</question>\n";

    const SQL_QUERIES = [
        "DELETE FROM `setup_module` WHERE `setup_module`.`module` = 'Yotpo_Loyalty'",
        "ALTER TABLE `sales_order_item` DROP IF EXISTS `swell_redemption_id`",
        "ALTER TABLE `sales_order_item` DROP IF EXISTS `swell_points_used`",
        "ALTER TABLE `sales_order_item` DROP IF EXISTS `swell_user_agent`",
        "ALTER TABLE `quote_item` DROP IF EXISTS `swell_redemption_id`",
        "ALTER TABLE `quote_item` DROP IF EXISTS `swell_points_used`",
    ];

    protected $_origStoreId;

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
     * @param ResourceConnection
     */
    protected $_resourceConnection;

    /**
     * @var EavSetupFactory
     */
    private $_eavSetupFactory;

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
        $this->setName('yotpo:loyalty:uninstall')
            ->setDescription('Uninstall Yotpo - Remove all Yotpo attributes from DB. *This process is irreversible! You should backup first');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_resourceConnection = $this->_yotpoHelper->getObjectManager()->get('\Magento\Framework\App\ResourceConnection');
        $this->_eavSetupFactory = $this->_yotpoHelper->getObjectManager()->get('\Magento\Eav\Setup\EavSetupFactory');

        if (!$this->confirmQuestion(self::CONFIRM_MESSAGE, $input, $output)) {
            return;
        }

        $this->_registry->register('isYotpoResetCommand', true);
        $this->updateMemoryLimit();

        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->_eavSetupFactory->create();

        try {
            $output->writeln('<info>' . 'Uninstalling Yotpo (Imagine a spinning gif loager) ...' . '</info>');

            $eavAttributes = [
                'yotpo_force_cart_reload',
            ];

            $output->writeln('<info>' . 'Removing eav attributes ...' . '</info>');
            foreach ($eavAttributes as $attrCode) {
                $eavSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, $attrCode);
            }

            $output->writeln('<info>' . 'Removing quote/order item fields ...' . '</info>');

            foreach (self::SQL_QUERIES as $query) {
                try {
                    $this->_resourceConnection->getConnection()->query($query);
                } catch (\Exception $e) {
                    $output->writeln('<error>' . $e->getMessage() . '</error>');
                }
            }

            if ($this->confirmQuestion(self::RESET_CONFIG_CONFIRM_MESSAGE, $input, $output)) {
                $output->writeln('<info>' . 'Resetting all Yotpo configurations ...' . '</info>');
                $this->resetConfig();
            }

            $output->writeln('<info>' . 'Done :(' . '</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }

    /**
     * @method confirmQuestion
     * @param string $message
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    private function confirmQuestion(string $message, InputInterface $input, OutputInterface $output)
    {
        $confirmationQuestion = new ConfirmationQuestion($message, false);
        return (bool)$this->getHelper('question')->ask($input, $output, $confirmationQuestion);
    }

    private function resetConfig()
    {
        $this->_resourceConnection->getConnection()->delete(
            $this->_resourceConnection->getTableName('core_config_data'),
            "path LIKE '" . \Yotpo\Loyalty\Helper\Data::XML_PATH_ALL . "/%'"
        );
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
