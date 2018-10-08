<?php declare(strict_types=1);
namespace Onion\Cli\Config\Reader;

class IniReader implements ReaderInterface
{
    public function parseFile(string $filename): array
    {
        return parse_ini_file(
            $filename,
            true,
            INI_SCANNER_TYPED
        );
    }
}
