<?php declare(strict_types=1);
namespace App\Build;

use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Cli\Manifest\Loader;

class Command implements CommandInterface
{
    /** @var Loader  */
    private $loader;
    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }

    public function trigger(ConsoleInterface $console): int
    {
        $console->writeLine('Building');
        return 0;
    }
}
