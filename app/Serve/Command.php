<?php declare(strict_types=1);
namespace Onion\Tool\Serve;

use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Console\Interfaces\SignalAwareCommandInterface;
use Onion\Framework\Server\Interfaces\ServerInterface;
use function Onion\Framework\Loop\scheduler;

class Command implements CommandInterface, SignalAwareCommandInterface
{
    private $server;

    public function __construct(ServerInterface $server)
    {
        $this->server = $server;
    }

    public function trigger(ConsoleInterface $console)
    {
        scheduler()->add($this->server->start());

        return 0;
    }

    public function exit(\Onion\Framework\Console\Interfaces\ConsoleInterface $console, string $signal): void
    {
        $console->writeLine('%text:cyan%Server going down');
    }
}
