<?php declare(strict_types=1);
namespace Onion\Cli\Config\Reader;

use Symfony\Component\Yaml\Yaml;

class YamlReader implements ReaderInterface
{
    public function parseFile(string $filename): array
    {
        return Yaml::parseFile($filename);
    }
}
