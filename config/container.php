<?php

use Onion\Framework\Config\Container as Configuration;
use Onion\Framework\Config\Reader\IniReader;
use Onion\Framework\Config\Reader\PhpReader;
use Onion\Framework\Config\Loader;
use Onion\Framework\Dependency\Container;
use Onion\Framework\Dependency\Interfaces\DelegateContainerInterface;
use Onion\Framework\Dependency\Traits\DelegateContainerTrait;
use Psr\Container\ContainerInterface;

$loader = new Loader();
$loader->registerReader(['php'], new PhpReader());
$loader->registerReader(['env', 'ini'], new IniReader());

$configs = $loader->loadDirectories('dist', [__DIR__]);

$proxy = new class implements ContainerInterface, DelegateContainerInterface
{
    use DelegateContainerTrait;

    public function get(string $id)
    {
        foreach ($this->getAttachedContainers() as $i => $container) {
            if ($container->has($id)) {
                return $container->get($id);
            }
        }

        throw new \Onion\Framework\Dependency\Exception\UnknownDependencyException(
            "Unable to resolve '{$id}'"
        );
    }
};

$cfg = new Configuration($configs);
$proxy->attach($cfg);
$proxy->attach(new Container([
    'factories' => $configs['factories'] ?? [],
    'invokables' => $configs['invokables'] ?? [],
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

return $proxy;
