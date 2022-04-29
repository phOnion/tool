<?php

namespace Onion\Tool\Package\Service;

use AppendIterator;
use CallbackFilterIterator;
use Onion\Framework\Console\Components\Animation;
use Onion\Framework\Console\Components\Progress;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Packer
{
    private AppendIterator $directories;
    public function __construct(
        private readonly string $filename,
        private readonly array $ignores = []
    ) {
        $this->directories = new AppendIterator();
    }

    public function addDirectory(string $directory): void
    {
        $this->directories->append(new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                str_replace(['\\', '/'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $directory),
                \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
            ),
        ));
    }

    public function pack($directory, ?ConsoleInterface $console): \Phar
    {
        $excludePattern = $this->compileIgnorePattern($directory);
        $progress = new Progress(64, PHP_INT_MAX / 2, cursor: new Animation([
            "\u{2846}",
            "\u{2807}",
            "\u{280B}",
            "\u{2819}",
            "\u{2838}",
            "\u{28b0}",
            "\u{28e0}",
            "\u{28c4}",
        ], fn (string $frame): string => "<color text='green'>{$frame}</color>"));
        $progress->setFormat(
            '{cursor} <color text="cyan">Loading files</color>'
        );
        $console->hideCursor();
        $progress->flush($console);
        $phar = new \Phar($this->filename);

        $this->directories->append(new CallbackFilterIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $directory,
                \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
            )),
            fn (string $path) => preg_match($excludePattern, $path) !== 1,
        ));

        $verboseOutput = $console->hasArgument('verbose');
        foreach ($this->directories as $item) {
            if (!(is_dir($item) && isset($phar[$item]))) {
                $phar->addFile($item, substr($item, strlen(getcwd()) + 1));
                if ($verboseOutput) {
                    $console->overwrite("Added {$item}\n");
                }
                $progress->advance();
                $progress->flush($console);
            }
        }
        $console->showCursor();

        return $phar;
    }

    private function compileIgnorePattern(string $base = '.'): string
    {
        return strtr(str_replace('/', DIRECTORY_SEPARATOR, "~^(?:{$base}/)(?=" . implode('|', array_map(trim(...), $this->ignores)) . ')~i'), [
            '.' => '\.',
            '*' => '.*',
            '/' => DIRECTORY_SEPARATOR,
            '\\' => '\\\\',
        ]);
    }
}
