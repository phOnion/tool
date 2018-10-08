<?php

use Onion\Framework\Dependency\Container;
use function Onion\Framework\merge;

$config = include __DIR__ . '/config.global.php';
$console = include __DIR__ . '/console.global.php';
$container = include __DIR__ . '/container.global.php';

$cfg = merge($config, merge($console, $container));

return new Container($cfg);
