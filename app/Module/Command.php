<?php declare(strict_types=1);
namespace Onion\Tool\Module;

use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Cli\Manifest\Loader;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Tool\Module\Service\ActionStrategy;

class Command implements CommandInterface
{
    /** @var Loader $loader */
    private $loader;

    /** @var Manifest $manifest */
    private $manifest;


    /** @var ActionStrategy $actions */
    private $actions;
    public function __construct(Loader $loader, ActionStrategy $actions)
    {
        $this->loader = $loader;
        $this->actions = $actions;
    }

    public function trigger(ConsoleInterface $console): int
    {
        // onion module install onion/framework
        $module = $console->getArgument('module', '');
        $action = $console->getArgument('action', '');

        $executor = $this->actions->getAction($action);

        if (!$executor->validateModule($module)) {
            throw new \InvalidArgumentException(
                'Module name not provided or it is invalid'
            );
            return 1;
        }

        return $executor->perform($console, $module);
    }
}
