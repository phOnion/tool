<?php declare(strict_types=1);
namespace App\Initialize;

use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Cli\Manifest\Loader;
use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Cli\SemVer\Version;

class Command implements CommandInterface
{
    /** @var Loader $loader */
    private $loader;

    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }

    public function trigger(ConsoleInterface $console): int
    {
        $manifest = new Manifest(basename(getcwd()), '0.0.0');
        if ($this->loader->manifestExists()) {
            if ($console->confirm('%text:yellow%Manifest already exists. Overwrite?', 'n') === 'n') {
                return 0;
            }

            $manifest = $this->loader->getManifest();
        }

        $console->writeLine('%text:cyan%Initializing default configuration');
        $manifest = $manifest->setName(
            $console->prompt('%text:green%Package name', $manifest->getName())
        )->setVersion((string) new Version($console->getArgument(
            'version',
            $console->prompt('%text:green%Version', $manifest->getVersion())
        )));

        $composer = json_decode(file_get_contents(getcwd() . '/composer.json'), true);
        $composer['extra']['onion']['manifest']['filename'] = 'onion.json';

        $console->writeLine('%text:cyan%Generated manifest file `onion.json`');

        $this->loader->saveManifest(getcwd(), $manifest);

        return 0;
    }
}
