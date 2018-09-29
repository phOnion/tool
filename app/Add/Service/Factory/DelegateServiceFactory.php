<?php declare(strict_types=1);
namespace App\Add\Service\Factory;

use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface;
use App\Add\Service\DelegateService;
use App\Add\Strategies\MaintainerStrategy;
use App\Add\Strategies\LinkStrategy;
use App\Add\Strategies\CommandStrategy;

class DelegateServiceFactory implements FactoryInterface
{
    public function build(ContainerInterface $container): DelegateService
    {
        $service = new DelegateService();

        return $service->addStrategy(new LinkStrategy())
            ->addStrategy(new CommandStrategy);
    }
}
