<?php
use Onion\Framework\Common\Config\Reader\IniReader;
use Onion\Framework\Common\Config\Reader\JsonReader;
use Onion\Framework\Common\Config\Reader\PhpReader;
use Onion\Framework\Common\Config\Reader\YamlReader;
use Onion\Tool\Module\Actions\InstallAction;
use Onion\Tool\Module\Actions\ListAction;
use Onion\Tool\Module\Actions\LoadAction;
use Onion\Tool\Module\Actions\ShowAction;
use Onion\Tool\Module\Actions\UninstallAction;
use Onion\Tool\Module\Actions\UnloadAction;
use Onion\Tool\Module\Actions\UpdateAction;

return [
    'config' => [
        'readers' => [[
            'extensions' => ['json'],
            'reader' => JsonReader::class,
        ], [
            'extensions' => ['yml', 'yaml'],
            'reader' => YamlReader::class,
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
            'install' => InstallAction::class,
            'show' => ShowAction::class,
            'list' => ListAction::class,
            'update' => UpdateAction::class,
        ],
    ],
    'listeners' => [],
];
