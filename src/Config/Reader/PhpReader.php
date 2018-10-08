<?php declare(strict_types=1);
namespace Onion\Cli\Config\Reader;

class PhpReader implements ReaderInterface
{
    public function parseFile(string $filename): array
    {
        return include $filename;
    }
}
