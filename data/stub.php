#!/usr/bin/env php
<?php
use function Onion\Framework\Loop\scheduler;
use Onion\Framework\Dependency\ProxyContainer;
use Psr\Http\Message\ServerRequestInterface;

if (!in_array('phar', stream_get_wrappers()) && class_exists('Phar')) {
    fwrite(fopen('php://stderr', 'wb'), 'Phar Extension not available');
    exit(1);
}
Phar::interceptFileFuncs();

set_include_path('phar://' . __FILE__ . PATH_SEPARATOR . get_include_path());
$internal = 'phar://' . __FILE__ . '/container.generated.php';
$locals = include $internal;

$proxy = new ProxyContainer();
foreach ($locals as $c) {
    $proxy->attach($c);
}

$external = getcwd() . '/container.generated.php';
if (file_exists($external)) {
    if (hash_file('sha256', $internal) !== hash_file('sha256', $external)) {
        foreach (include getcwd() . '/container.generated.php' as $c) {
            $proxy->attach($c);
        }
    }
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
                $proxy->attach($c);
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
    $instance = $proxy->get(\Onion\Framework\Application\Interfaces\ApplicationInterface::class);
    $args = [$proxy->get(ServerRequestInterface::class)];
}

if ($interface === 'cli') {
    $instance = $proxy->get(\Onion\Framework\Console\Interfaces\ApplicationInterface::class);
    $args = [$argv ?? [], $proxy->get(\Onion\Framework\Console\Interfaces\ConsoleInterface::class)];
}

$code = $instance->run(...$args) ?? 0;
scheduler()->start();
exit($code);
__HALT_COMPILER();
