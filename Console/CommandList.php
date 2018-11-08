<?php

namespace Yotpo\Loyalty\Console;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class CommandList
 */
class CommandList implements \Magento\Framework\Console\CommandListInterface
{
    /**
     * Object Manager
     *
     * @var ObjectManagerInterface
     */
    private $_objectManager;

    /**
     * @method __construct
     * @param  ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Gets list of command classes
     *
     * @return string[]
     */
    protected function getCommandsClasses()
    {
        return [
            'Yotpo\Loyalty\Console\Command\SyncCommand',
            'Yotpo\Loyalty\Console\Command\RemoveOldSyncRecordsCommand',
            'Yotpo\Loyalty\Console\Command\UninstallCommand',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCommands()
    {
        $commands = [];
        foreach ($this->getCommandsClasses() as $class) {
            if (class_exists($class)) {
                $commands[] = $this->_objectManager->get($class);
            } else {
                throw new \Exception('Class ' . $class . ' does not exist');
            }
        }
        return $commands;
    }
}
