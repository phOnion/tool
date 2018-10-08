<?php

use Onion\Cli\Manifest\Entities\Command;
use Onion\Cli\Manifest\Entities\Index;
use Onion\Cli\Manifest\Entities\Link;
use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Cli\Manifest\Entities\Repository;
use Onion\Cli\Manifest\Factory\LocalManifestFactory;
use Onion\Cli\Manifest\Loader;
use Onion\Console\Application\Application;
use Onion\Framework\Dependency\Container;

$container = new Container([
    'manifest' => [
        'map' => [
            'commands' => Command::class,
            'links' => Link::class,
            'index' => Index::class,
            'repositories' => Repository::class,
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
    'commands' => $commands,
    'factories' => [
        Manifest::class => LocalManifestFactory::class,
    ]
];
