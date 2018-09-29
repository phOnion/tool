<?php declare(strict_types=1);
namespace App\Initialize;

use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Cli\Manifest\Loader;
use Onion\Cli\Manifest\Entities\Manifest;

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
            if ($console->choice('%text:yellow%Manifest already exists. Overwrite?', ['y', 'n'], 'n') === 'n') {
                return 0;
            }

            $manifest = $this->loader->getManifest();
        }

        $console->writeLine('%text:cyan%Initializing default configuration');
        $manifest->setName($console->prompt('%text:green%Package name', $manifest->getName()));
        if (!$console->hasArgument('version')) {
            $manifest->setVersion(
                $console->prompt('%text:green%Version', $manifest->getVersion())
            );
        }

        $this->loader->saveManifest(getcwd(), $manifest);

        return 0;
    }
}
