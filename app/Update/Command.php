<?php declare(strict_types=1);
namespace Onion\Tool\Update;

use Humbug\SelfUpdate\Updater;
use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Cli\UpdateStrategy;

class Command implements CommandInterface
{
    /** @var UpdateStrategy $strategy */
    private $strategy;
    private $localFile;

    public function __construct(UpdateStrategy $strategy)
    {
        $this->localFile = \Phar::running(true);
        $this->strategy = $strategy;
    }

    public function trigger(ConsoleInterface $console): int
    {
        if ($this->localFile === '') {
            $console->writeLine('%text:red%Unable to update unpacked project');
            return 1;
        }

        $updater = new Updater($this->localFile, true);
        $updater->setStrategyObject($this->strategy);

        if ($console->hasArgument('rollback')) {
            if ($updater->rollback()) {
                $console->writeLine('%text:green%Rollback successful');
                return 0;
            }

            $console->writeLine('%text:Rollback failed, consider manual rollback%');
            return 1;
        }

        if (!$updater->hasUpdate()) {
            $console->writeLine('%text:cyan%You are using the latest version');
            return 0;
        }

        if ($updater->update()) {
            $console->writeLine(
                "%text:green%Updated to %text:yellow%{$updater->getNewVersion()}"
            );
            return 0;
        }

        return 1;
    }
}
