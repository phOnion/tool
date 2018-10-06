<?php declare(strict_types=1);
namespace App\Link;

use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Cli\Manifest\Loader;
use Onion\Cli\Manifest\Entities\Link;

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

        if ($console->hasArgument('add')) {
            $manifest = $manifest->addLink(
                new Link(
                    filter_var($console->prompt('Title'), FILTER_SANITIZE_STRING | FILTER_SANITIZE_SPECIAL_CHARS),
                    filter_var($console->prompt('URL'), FILTER_SANITIZE_STRING),
                    filter_var($console->prompt('Language (optional)', ''), FILTER_SANITIZE_STRING | FILTER_SANITIZE_SPECIAL_CHARS)
                )
            );
        }

        if ($console->hasArgument('list')) {
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
        }

        $this->loader->saveManifest(getcwd(), $manifest);

        return 0;
    }
}
