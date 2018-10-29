<?php
use Onion\Cli\Config\Reader\JsonReader;
use Onion\Cli\Config\Reader\YamlReader;
use Onion\Cli\Config\Reader\PhpReader;
use Onion\Cli\Config\Reader\IniReader;

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
    ]
];
