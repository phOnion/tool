<?php

use Onion\Framework\Common\Config\Container as Configuration;
use Onion\Framework\Common\Config\Loader;
use Onion\Framework\Common\Config\Reader\IniReader;
use Onion\Framework\Common\Config\Reader\PhpReader;
use Onion\Framework\Dependency\Container;
use Onion\Framework\Dependency\DelegateContainer;
use Onion\Framework\Common\Config\Reader\YamlReader;
use Psr\Container\ContainerInterface;

$loader = new Loader();
$loader->registerReader(['php'], new PhpReader());
$loader->registerReader(['env', 'ini'], new IniReader());
$loader->registerReader(['yml', 'yaml'], new YamlReader());

$configs = $loader->loadDirectories('dist', [getcwd(), __DIR__]);
$configuration = new Configuration($configs);

$container = new Container([
    'factories' => $configs['factories'] ?? [],
    'invokables' => $configs['invokables'] ?? [],
    'shared' => $configs['shared'] ?? [],
]);

$containers = [$container, $configuration];
if (file_exists(__DIR__ . '/modules.global.php')) {
    $modules = include __DIR__ . '/modules.global.php';
    foreach ($modules['modules'] ?? [] as $file) {
        $containers[] = include __DIR__ . '/../' . $file;
    }
}
return new DelegateContainer($containers);
