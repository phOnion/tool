<?php
namespace Onion\Cli\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Event\ListenerProviders\SimpleProvider;

class EventProviderFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        $aggregate = [];

        foreach ($container->get('events.listeners') as $event => $listeners) {
            foreach ($listeners as $handler) {
                $aggregate[$event][] = $container->get($handler);
            }
        }

        return new SimpleProvider($aggregate);
    }
}
