<?php
use Onion\Framework\Common\Config\Reader\JsonReader;
use Onion\Framework\Common\Config\Reader\YamlReader;
use Onion\Framework\Common\Config\Reader\PhpReader;
use Onion\Framework\Common\Config\Reader\IniReader;

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
    'user' => 'env(USER)',
    'listeners' => [],
];
