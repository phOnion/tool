<?php declare(strict_types=1);
namespace Onion\Cli\Config\Reader;

class JsonReader implements ReaderInterface
{
    public function parseFile(string $filename): array
    {
        return json_decode($this->getJsonContents($filename), true);
    }

    private function getJsonContents(string $filename): string
    {
        return preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#", '', file_get_contents(
            $filename
        ));
    }
}
