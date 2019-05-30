<?php require_once __DIR__ . '/../vendor/autoload.php';

use Onion\Framework\Console\Console;
use Psr\Container\ContainerInterface;
use Onion\Console\Application\Application;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Dependency\DelegateContainer;
use Psr\Http\Message\ServerRequestInterface;

/** @var ContainerInterface $container */
$container = include __DIR__ . '/../config/container.php';
$containers = [$container];
foreach ([getcwd(), __DIR__] as $dir) {
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
$container = new DelegateContainer($containers);
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

exit($instance->run(...$args) ?? 0);
