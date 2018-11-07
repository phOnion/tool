<?php
use Psr\Http\Message\ServerRequestInterface;

if (!in_array('phar', stream_get_wrappers()) && class_exists('Phar')) {
    fwrite(fopen('php://stderr', 'wb'), 'Phar Extension not available');
    exit(1);
}
Phar::interceptFileFuncs();
// https://www.php-fig.org/psr/psr-4/examples/
spl_autoload_register(function ($class) {
    $composer = json_decode(file_get_contents('phar://' . __FILE__ . '/composer.json'), true);
    $autoload = $composer['autoload']['psr-4'] ?? [];

    foreach ($autoload as $prefix => $path) {
        $base_dir = 'phar://' . __FILE__ . "/{$path}/";
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
});

set_include_path('phar://' . __FILE__ . PATH_SEPARATOR . get_include_path());
return include 'phar://' . __FILE__ . '/container.generated.php';
__HALT_COMPILER();
