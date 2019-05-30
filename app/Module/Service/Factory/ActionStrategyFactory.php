<?php declare(strict_types=1);
namespace Onion\Tool\Module\Service\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Tool\Module\Service\ActionStrategy;

class ActionStrategyFactory implements FactoryInterface
{
    public function build(\Psr\Container\ContainerInterface $container)
    {
        $delegate = new ActionStrategy();
        if ($container->has('tool.actions')) {
            foreach ($container->get('tool.actions') as $action => $strategy) {
                $delegate->registerAction(
                    $action,
                    $container->get($strategy)
                );
            }
        }

        return $delegate;
    }
}
