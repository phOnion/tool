<?php
use Onion\Framework\Dependency\DelegateContainer;
use Psr\Http\Message\ServerRequestInterface;

if (!in_array('phar', stream_get_wrappers()) && class_exists('Phar')) {
    fwrite(fopen('php://stderr', 'wb'), 'Phar Extension not available');
    exit(1);
}

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    Phar::mount('vendor/composer.php', __DIR__ . '/../../vendor/autoload.php');
} else if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    Phar::mount('vendor/composer.php', __DIR__ . '/../vendor/autoload.php');
} else {
    Phar::mount('vendor/composer.php', 'phar://' . __FILE__ . '/vendor/autoload.php');
}
Phar::interceptFileFuncs();
require_once 'phar://' . __FILE__ . '/vendor/composer.php';

set_include_path('phar://' . __FILE__ . PATH_SEPARATOR . get_include_path());
$container = include 'phar://' . __FILE__ . '/container.generated.php';
if (is_dir(__DIR__ . '/modules/')) {
    $iterator = new \RegexIterator(new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator(
            __DIR__ . '/modules/',
            \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
        )
    ), '~\.phar$~', \RegexIterator::MATCH, \RegexIterator::USE_KEY);

    foreach ($iterator as $item) {
        /** @var \SplFileInfo $item */
        $containers[] = include $item->getRealPath();
    }

    $container = new DelegateContainer($containers);
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
