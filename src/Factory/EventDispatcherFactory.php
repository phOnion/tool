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
        $providers = new AggregateProvider;
        foreach($container->get('events.providers') as $provider) {
            $providers->addProvider($container->get($provider));
        }

        return new Dispatcher($providers);
    }
}
