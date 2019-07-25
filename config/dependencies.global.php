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
use Onion\Framework\Server\Interfaces\ServerInterface;
use Onion\Framework\Server\Server;
use Onion\Tool\Module\Service\ActionStrategy;
use Onion\Tool\Module\Service\Factory\ActionStrategyFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Onion\Framework\Event\Dispatcher;
use Psr\EventDispatcher\ListenerProviderInterface;
use Onion\Console\Router\Router;
use Onion\Cli\Factory\EventProviderFactory;

return [
    'invokables' => [
        ArgumentParserInterface::class => ArgumentParser::class,
        ServerInterface::class => Server::class,
        EventDispatcherInterface::class => Dispatcher::class,
        Router::class => Router::class,
    ],
    'factories' => [
        ConsoleInterface::class => ConsoleFactory::class,
        ApplicationInterface::class => ApplicationFactory::class,
        ConfigLoader::class => ConfigLoaderFactory::class,
        Loader::class => ManifestLoaderFactory::class,
        Client::class => HttpClientFactory::class,
        Manifest::class => LocalManifestFactory::class,
        ActionStrategy::class => ActionStrategyFactory::class,
        ListenerProviderInterface::class => EventProviderFactory::class,
    ],
];
