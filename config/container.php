<?php

use Onion\Cli\Manifest\Entities\Command;
use Onion\Cli\Manifest\Entities\Index;
use Onion\Cli\Manifest\Entities\Link;
use Onion\Cli\Manifest\Loader;
use Onion\Console\Application\Application;
use Onion\Console\Router\ArgumentParser;
use Onion\Console\Router\Factory\RouterFactory;
use Onion\Console\Router\Router;
use Onion\Framework\Console\Factory\ConsoleFactory;
use Onion\Framework\Console\Interfaces\ArgumentParserInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Dependency\Container;
use Onion\Framework\Dependency\DelegateContainer;
use Onion\Cli\Manifest\Entities\Manifest;

if (getenv('ENVIRONMENT') === 'production') {
    $directoryIterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator(__DIR__)
    );

    $dependencies = [];
    foreach ($directoryIterator as $file) {

    }
}

$loader = new Loader([
    'commands' => Command::class,
    'links' => Link::class,
    'index' => Index::class,
]);
$manifest = $loader->getManifest(__DIR__ . '/../');

$commands = [];
foreach ($manifest->getCommands() as $command) {
    /** @var Command $command */
    $parameters = [];
    foreach ($command->getParameters() as $name => $description) {
        $parameters[$name] = $description;
    }

    $cmd = [
        'name' => $command->getName(),
        'handler' => $command->getHandler(),
        'summary' => $command->getSummary(),
        'description' => $command->getDescription(),
    ];

    foreach ($command->getParameters() as $parameter) {
        $name = $parameter->getName();
        $cmd['parameters'][$parameter->getName()] = [
            'default' => $parameter->hasDefault() ? $parameter->getDefault() : null,
            'description' => $parameter->getDescription(),
            'type' => $parameter->getType(),
            'required' => $parameter->isRequired(),
        ];
    }

    $commands[] = $cmd;
}
$container = new Container($common + [
    'console' => [
        'stream' => 'php://stdout'
    ],
    'invokables' => [
        ArgumentParserInterface::class => ArgumentParser::class,
        Manifest::class => $manifest
    ],
    'factories' => [
        ConsoleInterface::class => ConsoleFactory::class,
        Router::class => RouterFactory::class
    ],
    'commands' => $commands
]);

return $container;
