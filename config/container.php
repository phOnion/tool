<?php

use Onion\Cli\Manifest\Loader;
use Onion\Console\Router\Router;
use App\Add\Service\DelegateService;
use Onion\Cli\Manifest\Entities\Command;
use Onion\Console\Router\ArgumentParser;
use Onion\Framework\Dependency\Container;
use Onion\Console\Application\Application;
use Onion\Console\Router\Factory\RouterFactory;
use Onion\Framework\Console\Factory\ConsoleFactory;
use App\Add\Service\Factory\DelegateServiceFactory;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Console\Interfaces\ArgumentParserInterface;
use Onion\Framework\Dependency\DelegateContainer;
use Onion\Cli\Manifest\Entities\Link;
use Onion\Cli\Manifest\Entities\Index;

if (getenv('ENVIRONMENT') === 'production') {
    $directoryIterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator(__DIR__)
    );

    $dependencies = [];
    foreach ($directoryIterator as $file) {

    }
}

$common = [
    'manifest' => [
        'map' => [
            'commands' => Command::class,
            'links' => Link::class,
            'index' => Index::class,
        ]
    ],
];

// Test
$container = new Container($common);
/** @var Loader $loader */
$loader = $container->get(Loader::class);
$commands = [];
foreach ($loader->getManifest()->getCommands() as $command) {
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
$manifestContainer = new Container($common + [
    'console' => [
        'stream' => 'php://stdout'
    ],
    // 'commands' => include __DIR__ . '/commands.php',
    'invokables' => [
        ArgumentParserInterface::class => ArgumentParser::class
    ],
    'factories' => [
        ConsoleInterface::class => ConsoleFactory::class,
        Router::class => RouterFactory::class,
        DelegateService::class => DelegateServiceFactory::class
    ],
    'commands' => $commands
]);

return $manifestContainer;
