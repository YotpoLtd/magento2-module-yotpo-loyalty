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

        $salesConnection = $setup->getConnection('sales');
        $checkoutConnection = $setup->getConnection('checkout');

        $salesOrderTableName = $salesConnection->getTableName('sales_order');
        $salesOrderItemTableName = $salesConnection->getTableName('sales_order_item');
        $quoteItemTableName = $salesConnection->getTableName('quote_item');

        $attributes = [
            'swell_redemption_id' => [
                'type'         => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length'       => '11',
                'label'        => 'Yotpo - Swell Redemption ID',
                'comment'      => 'Yotpo - Swell Redemption ID',
                'nullable'     => true,
            ],
            'swell_points_used' => [
                'type'         => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length'       => '11',
                'label'        => 'Yotpo - Swell Points Used',
                'comment'      => 'Yotpo - Swell Points Used',
                'nullable'     => true,
            ],
        ];

        foreach ($attributes as $code => $options) {
            //Order Item
            if (!$salesConnection->tableColumnExists($salesOrderItemTableName, $code)) {
                $salesConnection->addColumn($salesOrderItemTableName, $code, $options);
            }
            //Cart Item
            if (!$checkoutConnection->tableColumnExists($quoteItemTableName, $code)) {
                $checkoutConnection->addColumn($quoteItemTableName, $code, $options);
            }
        }

        if (!$salesConnection->tableColumnExists($salesOrderTableName, 'swell_user_agent')) {
            $salesConnection->addColumn(
                $salesOrderTableName,
                'swell_user_agent',
                [
                    'type'         => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length'       => 2000,
                    'label'        => 'Swell User-Agent',
                    'comment'      => 'Swell User-Agent',
                    'nullable'     => true,
                ]
            );
        }

        if (!$eavSetup->getAttributeId(\Magento\Customer\Model\Customer::ENTITY, 'yotpo_force_cart_reload')) {
            $eavSetup->addAttribute(
                \Magento\Customer\Model\Customer::ENTITY,
                'yotpo_force_cart_reload',
                [
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
                ]
            );
        }

        $setup->endSetup();
    }
}
