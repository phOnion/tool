<?php declare(strict_types=1);
namespace App\Add;

use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

interface StrategyInterface
{
    public function getType(): string;
    public function addValue(Manifest $manifest, array $values): Manifest;
    public function prompt(ConsoleInterface $console): array;
}
