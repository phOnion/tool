<?php
use Onion\Framework\Dependency\DelegateContainer;
use Psr\Http\Message\ServerRequestInterface;
use Onion\Framework\Dependency\ProxyContainer;

if (!in_array('phar', stream_get_wrappers()) && class_exists('Phar')) {
    fwrite(fopen('php://stderr', 'wb'), 'Phar Extension not available');
    exit(1);
}
Phar::interceptFileFuncs();

$autoload = [];
if (file_exists('phar://' . __FILE__ . '/autoload.php')) {
    $autoload = include 'phar://' . __FILE__ . '/autoload.php';

    if (isset($autoload['files'])) {
        foreach ($autoload['files'] as $file) {
            $file = 'phar://' . __FILE__ . "/{$file}";
            if (!file_exists($file)) {
                continue;
            }

            include $file;
        }

        unset($autoload['files']);
    }
}

spl_autoload_register(function ($class) use ($autoload) {
    $base = 'phar://' . __FILE__;
    $parts = explode('\\', $class);

    $segment = '';
    foreach ($parts as $part) {
        $segment .= "{$part}\\";
        foreach ($autoload as $type => $definitions) {
            if (isset($definitions[$segment])) {
                foreach ($definitions as $paths) {
                    foreach ($paths as $path) {
                        $relative_class = $class;

                        if ($type === 'psr-4') {
                            $len = strlen($segment);
                            $relative_class = substr($class, $len);
                        }

                        $file = "{$base}/{$path}/" . str_replace('\\', '/', $relative_class) . '.php';
                        if (strncmp($segment, $class, $len) !== 0 || !file_exists($file)) {
                            continue;
                        }

                        include $file;
                    }
                }
            }
        }
    }
}, false, true);
set_include_path('phar://' . __FILE__ . PATH_SEPARATOR . get_include_path());
$container = new ProxyContainer();
foreach (include 'phar://' . __FILE__ . '/container.generated.php' as $c) {
    $container->attach($c);
}

foreach ([getcwd(), __DIR__] as $dir) {
    if (is_dir("{$dir}/modules/")) {
        $iterator = new \RegexIterator(new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                "{$dir}/modules/",
                \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
            )
        ), '~\.phar$~', \RegexIterator::MATCH, \RegexIterator::USE_KEY);

        foreach ($iterator as $item) {
            foreach(include "phar://{$item}/entrypoint.php" as $c) {
                var_dump($c);
                $container->attach($c);
            }
        }
    }
}
$interface = php_sapi_name() === 'cli' ? 'cli' : 'web';

$instance = null;
$args = [];
if ($interface === 'web') {
    \Phar::mungServer(['REQUEST_URI','SCRIPT_NAME','SCRIPT_FILENAME','PHP_SELF']);
    \Phar::webPhar(null, $web);
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
__HALT_COMPILER();
