<?php
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
return include 'phar://' . __FILE__ . '/container.generated.php';
__HALT_COMPILER();
