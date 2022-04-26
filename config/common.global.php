<?php

use Onion\Framework\Config\Reader\IniReader;
use Onion\Framework\Config\Reader\JsonReader;
use Onion\Framework\Config\Reader\PhpReader;
use Onion\Framework\Config\Reader\YamlReader;
use Onion\Tool\Module\Actions\ListAction;
use Onion\Tool\Module\Actions\LoadAction;
use Onion\Tool\Module\Actions\ShowAction;
use Onion\Tool\Module\Actions\UninstallAction;
use Onion\Tool\Module\Actions\UnloadAction;

return [
    'config' => [
        'readers' => [[
            'extensions' => ['json'],
            'reader' => JsonReader::class,
        ], [
            'extensions' => ['php', 'inc'],
            'reader' => PhpReader::class,
        ], [
            'extensions' => ['ini', 'env'],
            'reader' => IniReader::class,
        ]],
    ],
    'tool' => [
        'actions' => [
            'load' => LoadAction::class,
            'unload' => UnloadAction::class,
            'uninstall' => UninstallAction::class,
            // 'install' => InstallAction::class,
            'show' => ShowAction::class,
            'list' => ListAction::class,
            // 'update' => UpdateAction::class,
        ],
    ],
    'listeners' => [],
];
