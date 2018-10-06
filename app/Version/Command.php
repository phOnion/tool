<?php declare(strict_types=1);
namespace Onion\Tool\Version;

use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Cli\Manifest\Loader;

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
        $manifest = $this->loader->getManifest();
        $console->writeLine("%text:cyan%Version %text:green%{$manifest->getVersion()}");

        return 0;
    }
}
