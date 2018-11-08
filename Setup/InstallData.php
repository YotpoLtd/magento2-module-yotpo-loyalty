<?php

namespace Yotpo\Loyalty\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
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
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->_eavSetupFactory->create(['setup' => $setup]);

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
            $setup->getConnection()->addColumn($setup->getTable('sales_order_item'), $code, $options);
            //Cart Item
            $setup->getConnection()->addColumn($setup->getTable('quote_item'), $code, $options);
        }

        $setup->endSetup();
    }
}
