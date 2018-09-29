<?php declare(strict_types=1);
namespace App\Version;

use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class Command implements CommandInterface
{
    public function trigger(ConsoleInterface $console): int
    {
        $console->writeLine("Version {$version}");

        return 0;
    }
}