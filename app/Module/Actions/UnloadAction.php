<?php declare(strict_types=1);
namespace Onion\Tool\Module\Actions;

class UnloadAction extends AbstractAction implements ActionInterface
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
        if (file_exists("{$mod}.unloaded")) {
            $console->writeLine("%text:yellow%Module '{$module}' is already unloaded");

            return 1;
        }

        if (!rename($mod, "{$mod}.unloaded")) {
            $console->writeLine(
                "%text:red%Unloading of module '{$module}' failed"
            );
            return 1;
        }

        $console->writeLine(
            "%text:cyan%Module '{$module}' unloaded successfully"
        );

        return 0;
    }
}
