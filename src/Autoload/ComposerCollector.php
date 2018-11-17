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

    public function collect(bool $includeDev = false): iterable
    {
        return $this->collectDir($this->baseDir, $includeDev);
    }

    private function collectDir(string $dir, bool $includeDev, string $vendorDir = 'vendor'): iterable
    {
        $collected = [];
        $result = [];

        if (!is_dir($dir)) {
            return $result;
        }
        $iterator = new \DirectoryIterator("$dir/");
        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            if ($item->getFilename() !== 'composer.json' || $item->isDir()) {
                continue;
            }

            $composer = json_decode(file_get_contents($item->getRealPath()), true);
            $vendorDir = $composer['config']['vendor-dir'] ?? $vendorDir ?? 'vendor';
            $result[$dir] = merge(
                $result[$dir] ?? [],
                $composer['autoload'] ?? []
            );



            foreach ($composer['require'] ?? [] as $package => $version) {
                $dir = "{$this->baseDir}/{$vendorDir}/{$package}";
                $result = merge(
                    $result ?? [],
                    $this->collectDir($dir, $includeDev, $vendorDir)
                );
            }

            if ($includeDev) {
                $result = merge(
                    $result ?? [],
                    $composer['autoload-dev'] ?? []
                );



                foreach ($composer['require-dev'] ?? [] as $package => $version) {
                    $dir = "{$this->baseDir}/{$vendorDir}/{$package}";

                    $result[$dir] = merge(
                        $result[$dir] ?? [],
                        $this->collectDir($dir, $includeDev, $vendorDir)
                    );
                }
            }
        }

        return $result;
    }

    public function resolve(bool $includeDev = false)
    {
        $classes = [
            'psr-4' => [],
            'psr-0' => [],
            'files' => [],
        ];
        $resolution = $this->collect($includeDev);
        foreach ($resolution as $folder => $autoload) {
            foreach ($autoload as $type => $namespaces) {
                if (!isset($classes[$type])) {
                    continue;
                }

                if ($type === 'files') {
                    $classes[$type] = array_unique(merge($classes[$type], array_map(function ($file) use ($folder) {
                        $folder = strtr($folder, [
                            $this->baseDir => '',
                        ]);

                        return "{$folder}/{$file}";
                    }, $namespaces)));
                    continue;
                }

                if ($type === 'classmap') {
                    trigger_error(
                        "Classmaps are not supported, the following directory will not be auto-loaded " .
                            strtr($folder, [
                                $this->baseDir => '',
                            ]),
                        E_USER_WARNING
                    );
                    continue;
                }

                foreach ($namespaces as $namespace => $location) {
                    $realPath = strtr(trim($folder, "/\\") . '/' . trim($location, "/\\"), [
                        $this->baseDir => '',
                    ]);

                    $classes[$type][$namespace][] = $realPath;
                }
            }
        }

        return $classes;
    }
}
