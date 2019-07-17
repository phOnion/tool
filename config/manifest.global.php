<?php
use Onion\Cli\Manifest\Entities\Command;
use Onion\Cli\Manifest\Entities\Link;
use Onion\Cli\Manifest\Entities\Repository;
use Onion\Cli\Manifest\Entities\Dependency;

return [
    'manifest' => [
        'map' => [
            'commands' => Command::class,
            'links' => Link::class,
            'repositories' => Repository::class,
            'dependencies' => Dependency::class,
        ]
    ]
];
