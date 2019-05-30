<?php

use Onion\Cli\Manifest\Entities\Command;
use Onion\Cli\Manifest\Entities\Dependency;
use Onion\Cli\Manifest\Entities\Link;
use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Cli\Manifest\Entities\Repository;
use Onion\Cli\Manifest\Factory\LocalManifestFactory;
use Onion\Console\Application\Application;
use Onion\Framework\Dependency\Container;
use Onion\Tool\Module\Actions\InstallAction;
use Onion\Tool\Module\Actions\ListAction;
use Onion\Tool\Module\Actions\LoadAction;
use Onion\Tool\Module\Actions\ShowAction;
use Onion\Tool\Module\Actions\UninstallAction;
use Onion\Tool\Module\Actions\UnloadAction;
use Onion\Tool\Module\Service\ActionStrategy;
use Onion\Tool\Module\Service\Factory\ActionStrategyFactory;
use Onion\Tool\Module\Actions\UpdateAction;

$container = new Container([
    'manifest' => [
        'map' => [
            'commands' => Command::class,
            'links' => Link::class,
            'repositories' => Repository::class,
            'dependencies' => Dependency::class,
        ],
    ],
    'factories' => [
        Manifest::class => LocalManifestFactory::class,
    ],
]);
$manifest = $container->get(Manifest::class);

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

return [
    'console' => [
        'stream' => 'php://stdout',
    ],
    'manifest' => [
        'map' => $container->get('manifest.map'),
    ],
    'tool' => [
        'actions' => [
            'load' => LoadAction::class,
            'unload' => UnloadAction::class,
            'uninstall' => UninstallAction::class,
            'install' => InstallAction::class,
            'show' => ShowAction::class,
            'list' => ListAction::class,
            'update' => UpdateAction::class,
        ],
    ],
    'commands' => $commands,
    'factories' => [
        Manifest::class => LocalManifestFactory::class,
        ActionStrategy::class => ActionStrategyFactory::class,
    ]
];
