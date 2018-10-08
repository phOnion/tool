<?php declare(strict_types=1);
namespace Onion\Cli\Config\Reader;

interface ReaderInterface
{
    public function parseFile(string $filename): array;
}
