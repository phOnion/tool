<?php declare(strict_types=1);
namespace Onion\Tool\Module\Actions;

class LoadAction extends AbstractAction implements ActionInterface
{
    public function perform(\Onion\Framework\Console\Interfaces\ConsoleInterface $console, string $module): int
    {
        if (!$this->validateModule($module)) {
            throw new \InvalidArgumentException(
                "Module name '{$module}' is not valid. Missing vendor separator `/`"
            );
        }

        list($vendor, $project)=array_map('strtolower', explode('/', $module));
        $mod = getcwd() . "/modules/{$vendor}/{$project}.phar";
        if (file_exists($mod)) {
            $console->writeLine("%text:yellow%Module '{$module}' is already loaded");

            return 1;
        }

        if (!rename("{$mod}.unloaded", $mod)) {
            $console->writeLine(
                "%text:red%Loading of module '{$module}' failed"
            );
            return 1;
        }

        $console->writeLine(
            "%text:cyan%Module '{$module}' loaded successfully"
        );

        return 0;
    }
}
