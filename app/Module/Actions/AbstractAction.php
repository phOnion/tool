<?php declare(strict_types=1);
namespace Onion\Tool\Module\Actions;

abstract class AbstractAction implements ActionInterface
{
    public function validateModule(string $module)
    {
        return stripos($module, '/') !== false;
    }
}
