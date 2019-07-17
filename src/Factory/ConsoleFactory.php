<?php
namespace Onion\Cli\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Console\Console;

class ConsoleFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        return new Console(fopen('php://stdout', 'rb+'));
    }
}
