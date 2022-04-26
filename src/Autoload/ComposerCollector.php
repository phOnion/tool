<?php

namespace Onion\Cli\Autoload;

use function Onion\Framework\merge;

class ComposerCollector
{
    /** @var string $baseDir */
    private $baseDir;

    public function __construct(string $baseDir)
    {
        $this->baseDir = $baseDir;
    }

    public function collect(bool $standalone, bool $includeDev = false): iterable
    {
        return $this->collectDir($this->baseDir, $standalone, $includeDev);
    }

    private function collectDir(string $dir, bool $standalone, bool $includeDev, string $vendorDir = 'vendor'): iterable
    {
        $result = [];

        if (!is_dir("{$dir}/")) {
            return $result;
        }

        $iterator = new \DirectoryIterator("$dir/");
        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            if ($item->getFilename() !== 'composer.json' || $item->isDir()) {
                continue;
            }

            $composer = json_decode(file_get_contents($item->getRealPath()), true);
            foreach ($composer['autoload'] ?? [] as $type => $namespaces) {
                foreach ($namespaces as $namespace => $path) {
                    $temp = str_replace('//', '/', "{$dir}/{$path}");
                    if (isset($result[$type][$namespace]) && in_array($temp, $result[$type][$namespace])) {
                        continue;
                    }

                    $result[$type][$namespace][] = $temp;
                }
            }

            $vendorDir = $composer['config']['vendor-dir'] ?? $vendorDir ?? 'vendor';

            if ($standalone) {
                foreach ($composer['require'] ?? [] as $package => $version) {
                    $dir = "{$vendorDir}/{$package}/";
                    $result = merge(
                        $result ?? [],
                        $this->collectDir($dir, $standalone, $includeDev, $vendorDir)
                    );
                }
            }

            if ($includeDev) {
                foreach ($composer['autoload-dev'] ?? [] as $type => $namespaces) {
                    foreach ($namespaces as $namespace => $path) {
                        $temp = str_replace('//', '/', "{$dir}/{$path}");
                        if (isset($result[$type][$namespace]) && in_array($temp, $result[$type][$namespace])) {
                            continue;
                        }

                        $result[$type][$namespace][] = $temp;
                    }
                }


                if ($standalone) {
                    foreach ($composer['require-dev'] ?? [] as $package => $version) {
                        $dir = "{$vendorDir}/{$package}";

                        $result = merge(
                            $result ?? [],
                            $this->collectDir($dir, $standalone, $includeDev, $vendorDir)
                        );
                    }
                }
            }
        }

        return $result;
    }

    public function resolve(bool $standalone, bool $includeDev = false)
    {
        $classes = [
            'psr-4' => [],
            'psr-0' => [],
            'files' => [],
        ];
        $resolution = $this->collect($standalone, $includeDev);
        foreach ($resolution as $type => $autoload) {
            foreach ($autoload as $namespace => $folders) {
                if (!isset($classes[$type])) {
                    continue;
                }

                if ($type === 'files') {
                    $classes[$type] = array_unique(merge($classes[$type], array_map(function ($file) {
                        return str_replace("{$this->baseDir}/", '', $file);
                    }, $folders)));
                    continue;
                }

                if ($type === 'classmap') {
                    trigger_error("Classmaps are not supported", E_USER_WARNING);
                    continue;
                }

                foreach ($folders as $location) {
                    if (in_array($location, $classes[$type][$namespace] ?? [])) {
                        continue;
                    }

                    $classes[$type][$namespace][] = str_replace("{$this->baseDir}/", '', $location);
                }
            }
        }

        ksort($classes['psr-4']);
        ksort($classes['psr-0']);

        return $classes;
    }
}
