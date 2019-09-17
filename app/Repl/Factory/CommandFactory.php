<?php
namespace Onion\Tool\Repl\Factory;

use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Tool\Repl\Command;
use Psr\Container\ContainerInterface;

class CommandFactory implements FactoryInterface
{
    public function build(ContainerInterface $container)
    {
        return new Command(
            $container->get(Manifest::class),
            $container
        );
    }
}
