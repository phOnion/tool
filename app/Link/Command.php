<?php declare(strict_types=1);
namespace Onion\Tool\Link;

use Onion\Cli\Manifest\Entities\Link;
use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Cli\Manifest\Loader;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class Command implements CommandInterface
{
    private const ALLOWED_ACTIONS = [
        'list',
        'add',
        'delete',
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
                $this->listLinks($console, $manifest);
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
            case 'delete':
                if (count($manifest->getLinks()) === 0) {
                    $console->writeLine('%text:yellow%No links available');
                    return 1;
                }

                $this->listLinks($console, $manifest);
                $index = (int) $console->prompt("%text:cyan%Index of list to delete");
                $links = $manifest->getLinks();

                foreach ($links as $i => $link) {
                    if ($i+1 === $index) {
                        $confirm = $console->confirm(
                            "%text:yellow%Do you want to delete: {$link->getTitle()} ({$link->getHref()}",
                            'n'
                        );

                        if ($confirm) {
                            unset($links[$i]);
                            $this->loader->saveManifest(getcwd(), $manifest->withLinks($links));
                        }

                        return 0;
                    }
                }

                $console->writeLine("%text:yellow%No links removed");
                return 1;
                break;
        }

        return 0;
    }

    private function listLinks(ConsoleInterface $console, Manifest $manifest)
    {
        foreach ($manifest->getLinks() as $index => $link) {
            /** @var Link $link */
            $lang = $link->getLang();
            if ($lang !== '') {
                $lang = "%text:cyan%{$lang}%text:green% - ";
            }

            $index++;
            $console->writeLine(
                "{$index}) %text:green%{$link->getTitle()} - {$lang}%text:white%{$link->getHref()}"
            );
        }
    }
}
