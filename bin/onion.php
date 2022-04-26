<?php require_once __DIR__ . '/../vendor/autoload.php';

use function Onion\Framework\Loop\coroutine;
use Onion\Framework\Dependency\ProxyContainer;
use Psr\Http\Message\ServerRequestInterface;

/** @var ProxyContainer $container */
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

coroutine(static function () use ($instance, $args) {
    exit($instance->run(...$args) ?? 0);
});
