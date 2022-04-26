<?php

use Onion\Cli\Factory\ApplicationFactory;
use Onion\Cli\Factory\ConfigLoaderFactory;
use Onion\Cli\Factory\ConsoleFactory;
use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Cli\Manifest\Factory\LocalManifestFactory;
use Onion\Cli\Manifest\Factory\ManifestLoaderFactory;
use Onion\Cli\Manifest\Loader;
use Onion\Framework\Console\Router\ArgumentParser;
use Onion\Framework\Console\Router\Router;
use Onion\Framework\Config\Loader as ConfigLoader;
use Onion\Framework\Console\Interfaces\ApplicationInterface;
use Onion\Framework\Console\Interfaces\ArgumentParserInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Http\Client;
use Onion\Tool\Module\Service\ActionStrategy;
use Onion\Tool\Module\Service\Factory\ActionStrategyFactory;
use Onion\Tool\Repl\Command;
use Onion\Tool\Repl\Factory\CommandFactory;
use Psr\Http\Client\ClientInterface;

return [
    'invokables' => [
        ArgumentParserInterface::class => ArgumentParser::class,
        Router::class => Router::class,
        ClientInterface::class => Client::class,
    ],
    'factories' => [
        ConsoleInterface::class => ConsoleFactory::class,
        ApplicationInterface::class => ApplicationFactory::class,
        ConfigLoader::class => ConfigLoaderFactory::class,
        Loader::class => ManifestLoaderFactory::class,
        Manifest::class => LocalManifestFactory::class,
        ActionStrategy::class => ActionStrategyFactory::class,
        Command::class => CommandFactory::class,
    ],
];
