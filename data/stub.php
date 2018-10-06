<?php

if (!in_array('phar', stream_get_wrappers()) && class_exists('Phar')) {
    fwrite(fopen('php://stderr', 'wb'), 'Phar Extension not available');
    exit(1);
}

Phar::interceptFileFuncs();
set_include_path('phar://' . __FILE__ . PATH_SEPARATOR . get_include_path());

$webIndex = __WEB_STUB__;
$cliIndex = __CLI_STUB__;
if ($cliIndex && php_sapi_name() === 'cli') {
    return include $cliIndex;
    exit(0);
}

if ($webIndex) {
    \Phar::mungServer([
        'REQUEST_URI',
        'SCRIPT_NAME',
        'SCRIPT_FILENAME',
        'PHP_SELF',
    ]);
    \Phar::webPhar(null, $webIndex);

    return include $webIndex;
}

__HALT_COMPILER();
