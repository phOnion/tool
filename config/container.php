<?php

use Onion\Framework\Common\Config\Container as Configuration;
use Onion\Framework\Common\Config\Loader;
use Onion\Framework\Common\Config\Reader\IniReader;
use Onion\Framework\Common\Config\Reader\PhpReader;
use Onion\Framework\Dependency\Container;
use Onion\Framework\Common\Config\Reader\YamlReader;
use Onion\Framework\Dependency\ProxyContainer;
use Onion\Framework\Dependency\InflectorContainer;

$loader = new Loader();
$loader->registerReader(['php'], new PhpReader());
$loader->registerReader(['env', 'ini'], new IniReader());
$loader->registerReader(['yml', 'yaml'], new YamlReader());

$configs = $loader->loadDirectories('dist', [__DIR__]);

$proxy = new ProxyContainer;

$proxy->attach(new Configuration($configs));
$proxy->attach(new Container([
    'factories' => $configs['factories'] ?? [],
    'invokables' => $configs['invokables'] ?? [],
    'shared' => $configs['shared'] ?? [],
]));



if (file_exists(getcwd() . '/container.generated.php')) {
    foreach (include getcwd() . '/container.generated.php' as $container) {
        $proxy->attach($container);
    }
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
            if (!file_exists("phar://{$item}/entrypoint.php")) {
                trigger_error("Module file '{$item}' is not a valid module", E_USER_NOTICE);
                continue;
            }

            foreach (include "phar://{$item}/entrypoint.php" as $c) {
                $proxy->attach($c);
            }
        }
    }
}
$inflector = new InflectorContainer();
$inflector->wrap($proxy);

return $inflector;
