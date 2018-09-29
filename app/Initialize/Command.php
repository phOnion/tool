<?php declare(strict_types=1);
namespace App\Initialize;

use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class Command implements CommandInterface
{
    public function trigger(ConsoleInterface $console): int
    {
        $version = $console->getArgument('version', '1.0');
    }
}
