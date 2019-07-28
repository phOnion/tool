<?php declare(strict_types=1);
namespace Onion\Tool\Link;

use Onion\Cli\Manifest\Entities\Link;
use Onion\Cli\Manifest\Loader;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class Command implements CommandInterface
{
    private const ALLOWED_ACTIONS = [
        'list',
        'add',
    ];
    /** @var Loader $loader */
    private $loader;

    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }
    public function trigger(ConsoleInterface $console): int
    {
        $manifest = $this->loader->getManifest();
        $action = strtolower($console->getArgument('operation', 'list'));
        switch ($action) {
            default:
                $console->writeLine(
                    "%text:yellow%Unknown link action '{$action}'. Must be one of: " .
                    implode(', ', static::ALLOWED_ACTIONS)
                );
                break;
            case 'list':
                foreach ($manifest->getLinks() as $index => $link) {
                    /** @var Link $link */
                    $lang = $link->getLang();
                    if ($lang !== '') {
                        $lang = "%text:cyan%{$lang}%text:green% -";
                    }

                    $index++;
                    $console->writeLine(
                        "{$index}) %text:green%{$link->getTitle()} - {$lang}%text:white%{$link->getHref()}"
                    );
                }
                break;
            case 'add':
                $manifest = $manifest->addLink(
                    new Link(
                        filter_var($console->prompt('Title'), FILTER_SANITIZE_STRING | FILTER_SANITIZE_SPECIAL_CHARS),
                        filter_var($console->prompt('URL'), FILTER_SANITIZE_STRING),
                        filter_var($console->prompt('Language (optional)', ''), FILTER_SANITIZE_STRING | FILTER_SANITIZE_SPECIAL_CHARS)
                    )
                );

                $this->loader->saveManifest(getcwd(), $manifest);
                break;
        }

        return 0;
    }
}
