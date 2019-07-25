<?php
namespace Onion\Tool\Module\Factory;

use Onion\Tool\Module\Command;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Cli\Manifest\Loader;
use Onion\Tool\Module\Service\ActionStrategy;

class CommandFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        return new Command(
            $container->get(Loader::class),
        )
    }
}
