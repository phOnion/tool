<?php

namespace Onion\Tool\Package\Service;

use CallbackFilterIterator;
use Onion\Framework\Console\Components\Animation;
use Onion\Framework\Console\Components\Progress;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

use function Onion\Framework\generator;

class Packer
{
    private $directories = [];
    public function __construct(private readonly string $filename, private readonly array $ignores = [])
    {
    }

    public function addDirectory(string $directory): void
    {
        if (!in_array($directory, $this->directories)) {
            $this->directories[] = $directory;
        }
    }

    public function pack($directory, ?ConsoleInterface $console): \Phar
    {
        $excludePattern = $this->compileIgnorePattern();


        $progress = new Progress(64, PHP_INT_MAX / 2, cursor: new Animation([
            "\u{2846}",
            "\u{2807}",
            "\u{280B}",
            "\u{2819}",
            "\u{2838}",
            "\u{28b0}",
            "\u{28e0}",
            "\u{28c4}",
        ], fn (string $frame) => "<color text='green'>{$frame}</color>"));
        $progress->setFormat(
            '{cursor} <color text="cyan">Loading files</color>'
        );
        $console->hideCursor();
        $progress->flush($console);
        $phar = new \Phar($this->filename);

        $files = new CallbackFilterIterator(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $directory,
            \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
        )), fn ($item) => preg_match($excludePattern, substr($item, strlen($directory))) !== 1);

        foreach ($files as $file) {
            $phar->addFile(substr($file, strlen($directory)));
            $progress->advance();
            $progress->flush($console);
        }

        foreach ($this->directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
                $directory,
                \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
            ));

            foreach ($iterator as $item) {
                if (is_dir($item)) {
                    continue;
                }

                if (isset($phar[$item])) {
                    continue;
                }

                $phar->addFile($item);
                $progress->advance();
                $progress->flush($console);
            }
        }
        $console->showCursor();

        return $phar;
    }

    private function compileIgnorePattern(): string
    {
        return strtr(str_replace('/', DIRECTORY_SEPARATOR, "~^(" . implode('|', array_map(trim(...), $this->ignores)) . ')~i'), [
            '.' => '\.',
            '*' => '.*',
            '\\' => '\\\\',
        ]);
    }
}
