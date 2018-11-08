<?php

namespace Yotpo\Loyalty\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '0.2.0') < 0) {
            /**
            * Create table 'yotpo_sync_queue'
            */
            $table = $setup->getConnection()
                ->newTable($setup->getTable('yotpo_sync_queue'))
                ->addColumn(
                    'queue_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    11,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Queue ID'
                )
                ->addColumn(
                    'entity_type',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    20,
                    ['nullable' => true],
                    'Entity Type'
                )
                ->addColumn(
                    'entity_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    11,
                    ['unsigned' => true, 'nullable' => true],
                    'Entity ID'
                )
                ->addColumn(
                    'entity_status',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    30,
                    ['nullable' => true],
                    'Entity Status'
                )
                ->addColumn(
                    'store_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    11,
                    ['unsigned' => true, 'nullable' => true],
                    'Store ID'
                )
                ->addColumn(
                    'prepared_schema',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    3000,
                    ['nullable' => true],
                    'Prepared Schema'
                )
                ->addColumn(
                    'created_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'Created At'
                )
                ->addColumn(
                    'updated_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                    'Created At'
                )
                ->addColumn(
                    'sent',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    1,
                    ['unsigned' => true, 'nullable' => false, 'default' => '0'],
                    'Sent'
                )
                ->addColumn(
                    'sent_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                    null,
                    ['nullable' => true],
                    'Sent At'
                )
                ->addColumn(
                    'response',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    3000,
                    ['nullable' => true],
                    'Created At'
                )
                ->addColumn(
                    'has_errors',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    1,
                    ['unsigned' => true, 'nullable' => true, 'default' => '0'],
                    'Has Errors'
                )
                ->addColumn(
                    'tryouts',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    11,
                    ['unsigned' => true, 'nullable' => true, 'default' => '0'],
                    'Tryouts'
                )
                ->setComment("Yotpo Sync Queue");
            $setup->getConnection()->createTable($table);
        }

        $setup->endSetup();
    }
}
