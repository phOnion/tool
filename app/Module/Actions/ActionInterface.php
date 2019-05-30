<?php declare(strict_types=1);
namespace Onion\Tool\Module\Actions;

use Onion\Framework\Console\Interfaces\ConsoleInterface;


interface ActionInterface
{
    public function perform(ConsoleInterface $console, string $module): int;
}
