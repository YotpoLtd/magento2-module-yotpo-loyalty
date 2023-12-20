<?php

namespace Yotpo\Loyalty\Console\Command;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class UninstallCommand extends Command
{
    public const CONFIRM_MESSAGE = "<question>Are you sure you want to uninstall Yotpo? (y/n)[n]\n*This will remove all Yotpo attributes & data from DB.\n*This process is irreversible! You should backup first.</question>\n";
    public const RESET_CONFIG_CONFIRM_MESSAGE = "<question>Do you want to also remove all Yotpo configurations (reset to default)? (y/n)[n]</question>\n";

    public const SQL_QUERIES = [
        "default" => [
            "DELETE FROM `setup_module` WHERE `setup_module`.`module` = 'Yotpo_Loyalty'",
        ],
        "sales" => [
            "ALTER TABLE `sales_order_item` DROP IF EXISTS `swell_redemption_id`",
            "ALTER TABLE `sales_order_item` DROP IF EXISTS `swell_points_used`",
            "ALTER TABLE `sales_order_item` DROP IF EXISTS `swell_added_item`",
            "ALTER TABLE `sales_order` DROP IF EXISTS `swell_user_agent`",
        ],
        "checkout" => [
            "ALTER TABLE `quote_item` DROP IF EXISTS `swell_redemption_id`",
            "ALTER TABLE `quote_item` DROP IF EXISTS `swell_points_used`",
            "ALTER TABLE `quote_item` DROP IF EXISTS `swell_added_item`",
        ],
    ];

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

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
        $this->setName('yotpo:loyalty:uninstall')
            ->setDescription('Uninstall Yotpo - Remove all Yotpo attributes from DB. *This process is irreversible! You should backup first');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_resourceConnection = $this->_objectManager->get(\Magento\Framework\App\ResourceConnection::class);
        $this->_eavSetupFactory = $this->_objectManager->get(\Magento\Eav\Setup\EavSetupFactory::class);

        if (!$this->confirmQuestion(self::CONFIRM_MESSAGE, $input, $output)) {
            return;
        }

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

            foreach (self::SQL_QUERIES as $dbType => $queries) {
                $_connection = ($dbType === 'default') ? $this->_resourceConnection->getConnection() : $this->_resourceConnection->getConnection($dbType);
                foreach ($queries as $query) {
                    try {
                        $_connection->query($query);
                    } catch (\Exception $e) {
                        $output->writeln('<error>' . $e->getMessage() . '</error>');
                    }
                }
            }

            if ($this->confirmQuestion(self::RESET_CONFIG_CONFIRM_MESSAGE, $input, $output)) {
                $output->writeln('<info>' . 'Resetting all Yotpo configurations ...' . '</info>');
                $this->resetConfig();
            }

            $output->writeln('<info>' . 'Done :(' . '</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return 1;
        }

        return 0;
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
}
