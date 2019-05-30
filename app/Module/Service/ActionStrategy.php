<?php declare(strict_types=1);
namespace Onion\Tool\Module\Service;

use Onion\Tool\Module\Actions\ActionInterface;

class ActionStrategy
{
    private $actions = [];
    public function registerAction(string $actionName, ActionInterface $handler): void
    {
        $actionName = strtolower($actionName);

        $this->actions[$actionName] = $handler;
    }

    public function getAction(string $actionName): ActionInterface
    {
        $actionName = strtolower($actionName);
        if (!isset($this->actions[$actionName])) {
            throw new \InvalidArgumentException(
                "Unknown module action '{$actionName}'. Supported actions: " .
                    implode(', ', array_keys($this->actions))
            );
        }

        return $this->actions[$actionName];
    }
}
