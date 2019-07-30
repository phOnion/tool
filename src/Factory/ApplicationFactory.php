<?php
namespace Onion\Cli\Factory;

use Onion\Console\Application\Application;
use Onion\Console\Router\Router;
use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;

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
            } catch (ContainerErrorException $ex) {
                trigger_error("Unable to register '{$command['definition']}' command", E_USER_WARNING);
            }
        }

        return new Application($router);
    }
}
