<?php

use Doctrine\Common\Annotations\Reader;
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
use Onion\Console\Router\Router;
use Onion\Framework\Annotations\Factory\AnnotationReaderFactory;
use Onion\Framework\Common\Config\Loader as ConfigLoader;
use Onion\Framework\Console\Interfaces\ApplicationInterface;
use Onion\Framework\Console\Interfaces\ArgumentParserInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Tool\Module\Service\ActionStrategy;
use Onion\Tool\Module\Service\Factory\ActionStrategyFactory;

return [
    'invokables' => [
        ArgumentParserInterface::class => ArgumentParser::class,
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
        Reader::class => AnnotationReaderFactory::class,
    ],
];
