<?php

use Http\Client\Curl\Client;
use Onion\Cli\Factory\ApplicationFactory;
use Onion\Cli\Factory\ConfigLoaderFactory;
use Onion\Cli\Factory\ConsoleFactory;
use Onion\Cli\Factory\HttpClientFactory;
use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Cli\Manifest\Factory\LocalManifestFactory;
use Onion\Cli\Manifest\Factory\ManifestLoaderFactory;
use Onion\Cli\Manifest\Loader;
use Onion\Console\Router\ArgumentParser;
use Onion\Framework\Common\Config\Loader as ConfigLoader;
use Onion\Framework\Console\Interfaces\ApplicationInterface;
use Onion\Framework\Console\Interfaces\ArgumentParserInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Tool\Module\Service\ActionStrategy;
use Onion\Tool\Module\Service\Factory\ActionStrategyFactory;
use Onion\Framework\Server\Interfaces\ServerInterface;
use Onion\Framework\Server\Server;
use Psr\EventDispatcher\EventDispatcherInterface;
use Onion\Cli\Factory\EventDispatcherFactory;
use Onion\Framework\Http\RequestHandler\RequestHandler;
use Psr\Container\ContainerInterface;
use Onion\Framework\Http\Events\RequestEvent;
use Psr\EventDispatcher\ListenerProviderInterface;
use Onion\Cli\Factory\EventProviderFactory;
use Onion\Framework\Http\RequestHandler\Factory\RequestHandlerFactory;
use function GuzzleHttp\Psr7\str;

return [
    'invokables' => [
        ArgumentParserInterface::class => ArgumentParser::class,
        ServerInterface::class => Server::class,
    ],
    'factories' => [
        ConsoleInterface::class => ConsoleFactory::class,
        ApplicationInterface::class => ApplicationFactory::class,
        ConfigLoader::class => ConfigLoaderFactory::class,
        Loader::class => ManifestLoaderFactory::class,
        Client::class => HttpClientFactory::class,
        Manifest::class => LocalManifestFactory::class,
        ActionStrategy::class => ActionStrategyFactory::class,
        EventDispatcherInterface::class => EventDispatcherFactory::class,
        ListenerProviderInterface::class => EventProviderFactory::class,
        RequestHandler::class => RequestHandlerFactory::class,
        'request_dispatcher' => function (ContainerInterface $container) {
            $localContainer = getcwd() . '/container.generated.php';
            if (file_exists($localContainer)) {
                $container = include $localContainer;
            }
            /** @var RequestHandler $handler */
            $handler = $container->get(RequestHandler::class);

            return function(RequestEvent $event) use ($handler) {
                $response = $handler->handle($event->getRequest());
                // yield $event->getConnection()->wait(ResourceInterface::OPERATION_WRITE);
                $event->getConnection()->write(str($response));
                yield $event->getConnection()->close();
            };
        },
    ],
];
