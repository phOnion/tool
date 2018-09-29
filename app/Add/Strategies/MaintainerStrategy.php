<?php declare(strict_types=1);
namespace App\Add\Strategies;

use App\Add\StrategyInterface;
use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Cli\Manifest\Entities\Maintainer;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class MaintainerStrategy implements StrategyInterface
{
    public function getType(): string
    {
        return 'maintainer';
    }

    public function addValue(Manifest $manifest, array $values): Manifest
    {
        list($name, $email, $role)=$values;
        $maintainer = new Maintainer(
            $manifest->getSchema(),
            $name,
            $email,
            $role !== '' ? $role : null
        );

        return $manifest->addMaintainer($maintainer);
    }

    public function prompt(ConsoleInterface $console): array
    {
        $names = filter_var($console->prompt('Names'), FILTER_SANITIZE_STRING);

        $email = filter_var(strtolower($console->prompt('Email')), FILTER_SANITIZE_EMAIL);

        if ($names === false || $email === false) {
            throw new \InvalidArgumentException(
                'Provided name and/or email appear to be invalid'
            );
        }

        return [$names, $email, filter_var($console->prompt('Role (optional)', ''), FILTER_SANITIZE_STRING)];
    }
}
