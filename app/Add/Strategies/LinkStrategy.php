<?php declare(strict_types=1);
namespace App\Add\Strategies;

use App\Add\StrategyInterface;
use Onion\Cli\Manifest\Entities\Link;
use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class LinkStrategy implements StrategyInterface
{
    public function getType(): string
    {
        return 'link';
    }

    public function addValue(Manifest $manifest, array $values): Manifest
    {
        list($title, $href, $lang)=$values;

        return $manifest->addLink(
            new Link($title, $href, $lang !== '' ? $lang : null)
        );
    }

    public function prompt(ConsoleInterface $console): array
    {
        $title = filter_var($console->prompt('Title'), FILTER_SANITIZE_STRING | FILTER_SANITIZE_SPECIAL_CHARS);
        $href = filter_var($console->prompt('URL'), FILTER_SANITIZE_STRING);
        if (!filter_var($href, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(
                'Provided URL is invalid'
            );
        }

        return [$title, $href, filter_var($console->prompt('Language (optional)', ''), FILTER_SANITIZE_STRING | FILTER_SANITIZE_SPECIAL_CHARS)];
    }
}
