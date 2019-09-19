<?php
namespace Onion\Tool\Package\Service;

use Onion\Framework\Console\Components\Progress;
use Onion\Framework\Console\Interfaces\ConsoleInterface;


class Packer
{
    private $filename;
    private $ignores = [];
    private $directories = [];

    public function __construct(string $filename, array $rawIgnores = [])
    {
        $this->filename = $filename;
        $this->ignores = $rawIgnores;
    }

    public function addDirectory(string $directory): void {
        if (!in_array($directory, $this->directories)) {
            $this->directories[] = $directory;
        }
    }

    public function pack($directory, ?ConsoleInterface $console): \Phar
    {
        $excludePattern = $this->compileIgnorePattern();
        $files = preg_grep($excludePattern, array_map(function (string $item) {
            return stripos($item, './') === 0 ?
                substr($item, 2) : $item;
        }, iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $directory,
            \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
        )))), PREG_GREP_INVERT);
        foreach ($this->directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
                $directory,
                \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
            ));

            foreach ($iterator as $item) {
                if (is_dir($item) || stripos($item, '.git/') === 0) {
                    continue;
                }

                $files[] = $item;
            }
        }

        $progress = new Progress(64, count($files), [' ', "#"]);
        $progress->setFormat(
            '%text:cyan%Processing %text:yellow%{progress}%text:cyan%/{steps}'
        );
        $progress->display($console);

        $phar = new \Phar($this->filename);
        foreach ($files as $file) {
            $phar->addFile($file);
            if ($console instanceof ConsoleInterface) {
                $progress->increment(1);
                $progress->display($console);
            }
        }

        return $phar;
    }

    private function compileIgnorePattern(string $base = '.*'): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, "~^(" . implode('|', array_map(function ($line) {
            return trim(strtr($line, [
                '.' => '\.',
                '*' => '.*',
            ]));
        }, $this->ignores)) . ')~i');
    }
}
