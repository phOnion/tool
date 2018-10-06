<?php declare(strict_types=1);
namespace App\Command;

use Onion\Cli\Manifest\Entities\Command as Definition;
use Onion\Cli\Manifest\Loader;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

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

        if ($console->getArgument('add')) {
            $definition = $console->prompt('%text:green%Definition');
            $handler = $console->prompt('%text:green%Handler');
            if (!class_exists($handler)) {
                if ($console->confirm("%text:yellow%Provided command handler '{$handler}' does not exist. Continue?", 'n') === 'n') {
                    $console->writeLine('%text:red%Terminating');

                    return 1;
                }
            }

            $summary = $console->prompt('%text:green%Summary');
            $parameters = [];
            $console->write("%text:cyan%Add parameters interactively, you can add them later manually.");
            while ($console->confirm('%text:blue% Continue', 'y')) {

                $paramName = $console->prompt("%text:cyan%\tName");
                if (strlen($paramName) === 0) {
                    $console->write("%text:red%Parameter name can't be empty");
                    continue;
                }

                $paramDescription = $console->prompt("%text:cyan%\tDescription");
                if (strlen($paramDescription) === 0) {
                    $console->writeLine('%text:yellow%Parameter description is recommended');
                }

                $parameter = [
                    'name' => $paramName,
                    'description' => $paramDescription,
                    'type' => $console->prompt("%text:cyan%\tType", 'mixed'),
                    'required' => $console->confirm("%text:cyan%\tRequired", 'n'),
                ];

                $paramDefault = $console->prompt("%text:cyan%\tDefault value", '');
                if (strlen($paramDefault) > 0) {
                    $parameter['default'] = $paramDefault;
                }

                $parameters[] = $parameter;
            }

            $description = $console->prompt('%text:green%Description');
            $command = (new Definition(
                $definition,
                $handler,
                $summary,
                $parameters
            ))->withDescription($description);

            $manifest = $manifest->withCommands(
                array_merge($manifest->getCommands(), [$command])
            );
        }

        if ($console->hasArgument('delete') || $console->hasArgument('list')) {
            foreach ($manifest->getCommands() as $index => $command) {
                $index++;
                $console->writeLine("{$index}) %text:green%{$command->getName()} - %text:cyan%{$command->getSummary()}");
            }

            if ($console->hasArgument('list')) {
                return 0;
            }
            $commands = $manifest->getCommands();
            $index = $console->prompt('Command index to delete: ', '');
            $id = $index-1;
            if (!$index || !isset($commands[$id])) {
                $console->writeLine("Invalid selection '{$index}'");
                return 1;
            }

            if (!$console->confirm("%text:yellow%Are you sure you want to delete command {$index}?", 'n')) {
                $console->writeLine('%text:cyan%Canceled.');
                return 1;
            }

            unset($commands[$id]);
            $manifest = $manifest->withCommands(
                array_values($commands)
            );

        }

        $this->loader->saveManifest(getcwd(), $manifest);
        return 0;
    }
}
