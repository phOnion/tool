<?php declare(strict_types=1);
namespace Onion\Tool\Update;

use Humbug\SelfUpdate\Updater;
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
        $manifest = $this->manifest;
        $updater = new Updater(null, false, Updater::STRATEGY_GITHUB);
        $updater->getStrategy()->setPackageName($manifest->getName());
        $updater->getStrategy()->setPharName('onion.phar');
        $updater->getStrategy()->setCurrentLocalVersion($manifest->getVersion());
        if ($console->hasArgument('force')) {
            $updater->getStrategy()->setStability('any');
        }

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