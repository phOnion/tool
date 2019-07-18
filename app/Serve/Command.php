<?php declare(strict_types=1);
namespace Onion\Tool\Serve;

use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Console\Interfaces\SignalAwareCommandInterface;
use Onion\Framework\Server\Interfaces\ServerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class Command implements CommandInterface, SignalAwareCommandInterface
{
    private $server;
    private $dispatcher;

    public function __construct(ServerInterface $server, EventDispatcherInterface $dispatcher)
    {
        $this->server = $server;
        $this->dispatcher = $dispatcher;
    }

    public function trigger(ConsoleInterface $console)
    {
        $interface = (string) $console->getArgument('interface');
        $port = (int) $console->getArgument('port');
        $driverClass = (string) $console->getArgument('driver');

        $driver = new $driverClass($this->dispatcher);
        $this->server->attach(
            $driver,
            $interface,
            $port
        );

        return $this->server->start();
    }

    public function exit(\Onion\Framework\Console\Interfaces\ConsoleInterface $console, string $signal): void
    {
        $console->writeLine('%text:cyan%Server going down');
    }
}
