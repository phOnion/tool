<?php declare(strict_types=1);
namespace Onion\Cli\Config\Reader;

class JsonReader implements ReaderInterface
{
    public function parseFile(string $filename): array
    {
        return json_decode(file_get_contents(
            $filename
        ), true);
    }
}
