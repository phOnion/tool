<?php declare(strict_types=1);
namespace Onion\Tool\Version;

use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class Command implements CommandInterface
{
    /** @var Manifest $manifest */
    private $manifest;
    public function __construct(Manifest $manifest)
    {
        $this->manifest = $manifest;
    }

    public function trigger(ConsoleInterface $console): int
    {
        $console->writeLine("%text:cyan%Version %text:green%{$this->manifest->getVersion()}");

        return 0;
    }
}
