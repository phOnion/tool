<?php
namespace Onion\Cli\RouteCompiler;

use Doctrine\Common\Annotations\Reader;
use Onion\Framework\Annotations\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

class Compiler
{
    private $reader;
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }
    public function compileDir(string $dir)
    {
        $snapshot = get_declared_classes();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $dir,
                RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS
            )
        );
        $routes = [];
        foreach ($iterator as $item) {
            if ($item->isDir() || $item->getExtension() !== 'php') {
                continue;
            }

            if (@include_once((string) $item->getRealpath())) {
                $current = get_declared_classes();
                $classes = array_diff($current, $snapshot);

                foreach ($classes as $class) {
                    /** @var Route $annotation */
                    $annotation = $this->reader->getClassAnnotation(
                        new ReflectionClass($class),
                        Route::class
                    );

                    if (!$annotation) {
                        continue;
                    }

                    $routes[] = array_filter([
                        'pattern' => $annotation->getPattern(),
                        'middleware' => [
                            $class,
                        ],
                        'methods' => $annotation->getMethods(),
                        'headers' => $annotation->getHeaders(),
                    ], function ($route) {
                        return !empty($route);
                    });
                }

                $snapshot = $current;
            }
        }

        return $routes;
    }
}
