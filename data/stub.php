<?php
use Psr\Http\Message\ServerRequestInterface;

if (!in_array('phar', stream_get_wrappers()) && class_exists('Phar')) {
    fwrite(fopen('php://stderr', 'wb'), 'Phar Extension not available');
    exit(1);
}
Phar::interceptFileFuncs();
require_once 'phar://' . __FILE__ . '/vendor/autoload.php';

set_include_path('phar://' . __FILE__ . PATH_SEPARATOR . get_include_path());
$container = include 'phar://' . __FILE__ . '/container.generated.php';
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
    return $interface;
}

exit($instance->run(...$args) ?? 0);
__HALT_COMPILER();
