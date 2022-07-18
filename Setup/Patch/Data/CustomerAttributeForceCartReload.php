<?php

declare(strict_types=1);

namespace Yotpo\Loyalty\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Customer\Model\Customer;

class CustomerAttributeForceCartReload implements DataPatchInterface
{
    /**
     * ModuleDataSetupInterface
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * CustomerSetupFactory
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @method __construct
     * @param  ModuleDataSetupInterface $moduleDataSetup
     * @param  CustomerSetupFactory     $customerSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        if (!$customerSetup->getAttributeId(Customer::ENTITY, 'yotpo_force_cart_reload')) {
            $customerSetup->addAttribute(
                Customer::ENTITY,
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
                    'source'         => Boolean::class,
                ]
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
