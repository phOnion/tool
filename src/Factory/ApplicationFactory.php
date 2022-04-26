<?php

namespace Onion\Cli\Factory;

use Onion\Framework\Console\Application\Application;
use Onion\Framework\Console\Router\ArgumentParser;
use Onion\Framework\Console\Router\Router;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;

class ApplicationFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        /** @var Router $router */
        $router = $container->get(Router::class);
        foreach ($container->get('commands') as $command) {
            try {
                $router->addCommand(
                    $command['definition'],
                    $container->get($command['handler']),
                    $command
                );
            } catch (ContainerExceptionInterface $ex) {
                trigger_error("Unable to register '{$command['definition']}' command: {$ex->getMessage()}", E_USER_NOTICE);
            }
        }

        return new Application($router, $container->get(ArgumentParser::class));
    }
}
