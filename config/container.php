<?php

use Onion\Framework\Common\Config\Container as Configuration;
use Onion\Framework\Common\Config\Loader;
use Onion\Framework\Common\Config\Reader\IniReader;
use Onion\Framework\Common\Config\Reader\PhpReader;
use Onion\Framework\Dependency\Container;
use Onion\Framework\Dependency\DelegateContainer;
use Onion\Framework\Common\Config\Reader\YamlReader;

$loader = new Loader();
$loader->registerReader(['php'], new PhpReader());
$loader->registerReader(['env', 'ini'], new IniReader());
$loader->registerReader(['yml', 'yaml'], new YamlReader());

$configs = $loader->loadDirectories('dist', [__DIR__]);

$configuration = new Configuration($configs);

$container = new Container([
    'factories' => $configs['factories'] ?? [],
    'invokables' => $configs['invokables'] ?? [],
    'shared' => $configs['shared'] ?? [],
]);

$containers = [$container, $configuration];
if (file_exists(getcwd() . '/container.generated.php')) {
    array_unshift($containers, include getcwd() . '/container.generated.php');
}

foreach ([getcwd(), __DIR__ . '/../'] as $dir) {
    if (is_dir("{$dir}/modules/")) {
        $iterator = new \RegexIterator(new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                "{$dir}/modules/",
                \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
            )
        ), '~\.phar$~', \RegexIterator::MATCH, \RegexIterator::USE_KEY);

        foreach ($iterator as $item) {
            if (file_exists("phar://{$item}/entrypoint.php")) {
                $containers[] = include "phar://{$item}/entrypoint.php";
                continue;
            }

            trigger_error("Module file '{$item}' is not a valid module", E_USER_NOTICE);
        }
    }
}
return new DelegateContainer($containers);
