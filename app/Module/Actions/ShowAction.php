<?php declare(strict_types=1);
namespace Onion\Tool\Module\Actions;

use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Cli\Manifest\Entities\Manifest;

class ShowAction extends AbstractAction implements ActionInterface
{
    public function perform(ConsoleInterface $console, string $module): int
    {
        $location = getcwd() . "/modules/{$module}.phar";
        if (file_exists("$location")) {
            $data = new \Phar($location);
            if (!isset($data['onion.json'])) {
                $console->writeLine("%text:red%Module at {$location} does not contain a manifest");
                return 1;
            }
            $manifest = json_decode(file_get_contents($data['onion.json']->getPathName()), true);

            $license = '%text:yellow%Unknown';
            if (isset($manifest['license'])) {
                $licenseLoader = new \Composer\Spdx\SpdxLicenses();
                $identifier = $licenseLoader->getIdentifierByName($manifest['license']) ?? $manifest['license'];
                $licenseEntry = $licenseLoader->getLicenseByIdentifier($identifier ?? 'unknown');


                if ($licenseEntry !== null) {
                    $license = sprintf('%s%s(%s)%s', (
                        $licenseEntry[2] ? '%text:green%' : '%text:yellow%'
                    ), $licenseEntry[1], $licenseEntry[0], (
                        $licenseEntry[3] ? ' - %text:red% Deprecated' : ''
                    ));
                }
            }

            $console->writeLine("%text:cyan%Name\t\t%text:bold-green%{$manifest['name']}");
            $console->writeLine("%text:cyan%License\t\t%text:bold-green%{$license}");
            $console->writeLine("%text:cyan%Version\t\t%text:bold-green%{$manifest['version']}");
            $standalone = ($data->getMetadata()['standalone'] ?? false) ? 'Yes' : 'No';
            $console->writeLine("%text:cyan%Executable\t%text:bold-green%{$standalone}");
            $debug = ($data->getMetadata()['debug'] ?? false) ?
                '%text:bold-red%Yes' : '%text:bold-green%No';
            $console->writeLine("%text:cyan%Debug\t\t{$debug}");
            $compressed = $data->isCompressed() ? '%text:bold-green%Yes' : '%text:bold-yellow%No';
            $console->writeLine("%text:cyan%Compressed\t{$compressed}");
        }

        return 0;
    }
}
