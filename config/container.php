<?php

use Onion\Framework\Dependency\Container;
use function Onion\Framework\merge;
use Onion\Framework\Dependency\DelegateContainer;

$config = include __DIR__ . '/config.global.php';
$console = include __DIR__ . '/console.global.php';
$container = include __DIR__ . '/container.global.php';
$cfg = merge($config, merge($console, $container));

$container = new Container($cfg);

if (file_exists(__DIR__ . '/modules.global.php')) {
    $modules = include __DIR__ . '/modules.global.php';
    $containers = [$container];
    foreach ($modules['modules'] as $file) {
        $containers[] = include __DIR__ . '/../' . $file;
    }

    $container = new DelegateContainer($containers);
}
return $container;
