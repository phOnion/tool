<?php

$base = __DIR__;
$autoload = [];
if (file_exists("{$base}/autoload.generated.php")) {
    $autoload = include "{$base}/autoload.generated.php";

    if (isset($autoload['files'])) {
        foreach ($autoload['files'] as $file) {
            $file = "{$base}/{$file}";
            if (!file_exists($file)) {
                continue;
            }

            include_once $file;
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
                foreach ($definitions[$segment] as $path) {
                    $relative_class = $class;
                    $len = 0;

                    if ($type === 'psr-4') {
                        $len = strlen($segment);
                        $relative_class = substr($class, $len);
                    }

                    $path = trim($path, '\/');
                    $file = "{$base}/{$path}/" . str_replace('\\', '/', $relative_class) . '.php';

                    if (strncmp($segment, $class, $len) !== 0 || !file_exists($file)) {
                        continue;
                    }

                    include_once $file;

                    return true;
                }
            }
        }
    }
}, prepend: true);

unset($base, $autoload, $file);
