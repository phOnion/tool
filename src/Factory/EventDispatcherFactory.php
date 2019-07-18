<?php
namespace Onion\Cli\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Event\Dispatcher;
use Onion\Framework\Event\ListenerProviders\AggregateProvider;
use Psr\EventDispatcher\EventDispatcherInterface;

class EventDispatcherFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container): EventDispatcherInterface
    {
        $localContainerFile = getcwd() . '/container.generated.php';
        if (file_exists($localContainerFile)) {
            $container = include $localContainerFile;

            return $container->get(EventDispatcherInterface::class);
        }

        $providers = [];
        foreach($container->get('events.providers') as $provider) {
            
            $providers[] = $container->get($provider);
        }


        return new Dispatcher(
            new AggregateProvider($providers)
        );
    }
}
