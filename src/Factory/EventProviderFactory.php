<?php
namespace Onion\Cli\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Event\ListenerProviders\SimpleProvider;

class EventProviderFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        $listeners = [];

        foreach ($container->get('events.listeners') as $listener) {
            foreach ($listener['handlers'] as $handler) {
                $listeners[$listener['event']][] = $container->get($handler);
            }
        }

        return new SimpleProvider($listeners);
    }
}
