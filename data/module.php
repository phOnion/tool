<?php

$autoload = [];
$base = __DIR__;
if (file_exists("{$base}/autoload.php")) {
    $autoload = include "{$base}/autoload.php";

    if (isset($autoload['files'])) {
        foreach ($autoload['files'] as $file) {
            $file = "{$base}/autoload.php";
            if (!file_exists($file)) {
                continue;
            }

            include $file;
        }

        unset($autoload['files']);
    }
}

spl_autoload_register(function ($class) use ($autoload, $base) {
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

                        $file = "{$base}{$path}/" . str_replace('\\', '/', $relative_class) . '.php';
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

return include __DIR__ . '/container.generated.php';

