<?php require_once __DIR__ . '/../vendor/autoload.php';

use function Onion\Framework\Loop\scheduler;
use Onion\Framework\Dependency\InflectorContainer;
use Onion\Framework\Loop\Coroutine;
use Psr\Http\Message\ServerRequestInterface;

/** @var InflectorContainer $container */
$container = include __DIR__ . '/../config/container.php';
$interface = php_sapi_name() === 'cli' ? 'cli' : 'web';

$instance = null;
$args = [];

if ($interface === 'web') {
    $instance = $container->get(\Onion\Framework\Application\Interfaces\ApplicationInterface::class);
    $args = [$container->get(ServerRequestInterface::class)];
}

if ($interface === 'cli') {
    $instance = $container->get(\Onion\Framework\Console\Interfaces\ApplicationInterface::class);
    $args = [$argv ?? [], $container->get(\Onion\Framework\Console\Interfaces\ConsoleInterface::class)];
}

if (defined('ONION')) {
    return $instance;
}

$result = ($instance->run(...$args) ?? 0);

if ($result instanceof Coroutine) {
    scheduler()->add($result);
}

scheduler()->start();
if (is_int($result)) {
    exit($result);
}
