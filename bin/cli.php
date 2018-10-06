<?php require_once __DIR__ . '/../vendor/autoload.php';

use Onion\Framework\Console\Console;
use Psr\Container\ContainerInterface;
use Onion\Console\Application\Application;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

/** @var ContainerInterface $container */
$container = include __DIR__ . '/../config/container.php';
$console = $container->get(ConsoleInterface::class);

$container->get(Application::class)->run(
    $argv,
    $console
);
