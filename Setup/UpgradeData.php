<?php

namespace Yotpo\Loyalty\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * Upgrade Data script
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{

    /**
     * @var EavSetupFactory
     */
    private $_eavSetupFactory;

    /**
     * Init
     * @method __construct
     * @param  EavSetupFactory   $eavSetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
        $this->_eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->_eavSetupFactory->create(['setup' => $setup]);

        if (version_compare($context->getVersion(), '0.1.1') < 0) {
            $attributes = [
                'swell_user_agent' => [
                    'type'         => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'       => 2000,
                    'label'        => 'Swell User-Agent',
                    'comment'      => 'Swell User-Agent',
                    'nullable'     => true,
                ],
            ];

            foreach ($attributes as $code => $options) {
                //Order
                $setup->getConnection()->addColumn($setup->getTable('sales_order'), $code, $options);
            }
        }

        if (version_compare($context->getVersion(), '0.3.0') < 0) {
            $attributes = [
                'yotpo_force_cart_reload' => [
                    'type'           => 'int',
                    'label'          => 'Yotpo - force customerData cart reload',
                    'comment'        => 'Yotpo - force customerData cart reload',
                    'visible'        => false,
                    'nullable'       => true,
                    'user_defined'   => false,
                    'backend_type'   => 'int',
                    'frontend_input' => 'boolean',
                    'system'         => 0,
                    'default'        => '0',
                    'filterable'     => true,
                    'required'       => false,
                    'source'         => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                ],
            ];
            foreach ($attributes as $code => $options) {
                //Customer
                $eavSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY, $code, $options);
            }
        }

        $setup->endSetup();
    }
}
