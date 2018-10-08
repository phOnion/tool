<?php

use Onion\Cli\Config\Factory\LoaderFactory;
use Onion\Cli\Config\Loader;
use Onion\Console\Application\Application;
use Onion\Console\Router\ArgumentParser;
use Onion\Console\Router\Factory\RouterFactory;
use Onion\Console\Router\Router;
use Onion\Framework\Console\Factory\ConsoleFactory;
use Onion\Framework\Console\Interfaces\ApplicationInterface;
use Onion\Framework\Console\Interfaces\ArgumentParserInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

return [
    'invokables' => [
        ArgumentParserInterface::class => ArgumentParser::class,
        ApplicationInterface::class => Application::class,
    ],
    'factories' => [
        ConsoleInterface::class => ConsoleFactory::class,
        Router::class => RouterFactory::class,
        Loader::class => LoaderFactory::class,
    ],
];
