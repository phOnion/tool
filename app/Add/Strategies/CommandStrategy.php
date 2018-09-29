<?php declare(strict_types=1);
namespace App\Add\Strategies;

use App\Add\StrategyInterface;
use Onion\Cli\Manifest\Entities\Command;
use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class CommandStrategy implements StrategyInterface
{
    public function getType(): string
    {
        return 'command';
    }

    public function addValue(Manifest $manifest, array $values): Manifest
    {
        list(
            $definition,
            $handler,
            $summary,
            $description,
            $parameters
        ) = $values;

        $command = (new Command(
            $definition,
            $handler,
            $summary,
            $parameters
        ))->withDescription($description);

        return $manifest->withCommands(
            array_merge($manifest->getCommands(), [$command])
        );
    }

    public function prompt(ConsoleInterface $console): array
    {
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
                'required' => $console->confirm("%text:cyan%\tRequired", 'n') === 'y',
            ];

            $paramDefault = $console->prompt("%text:cyan%\tDefault value", '');
            if (strlen($paramDefault) > 0) {
                $parameter['default'] = $paramDefault;
            }

            $parameters[] = $parameter;
        }

        $description = $console->prompt('%text:green%Description');

        return [
            $definition,
            $handler,
            $summary,
            $description,
            $parameters
        ];
    }
}
