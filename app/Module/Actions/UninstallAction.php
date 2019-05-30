<?php declare(strict_types=1);
namespace Onion\Tool\Module\Actions;

class UninstallAction extends AbstractAction implements ActionInterface
{
    /** @var UnloadAction $unloadAction */
    private $unloadAction;

    public function __construct(UnloadAction $unloadAction)
    {
        $this->unloadAction = $unloadAction;
    }

    public function perform(\Onion\Framework\Console\Interfaces\ConsoleInterface $console, string $module): int
    {
        $this->unloadAction->perform($console, $module);

        list($vendor, $project)=array_map('strtolower', explode('/', $module));
        unlink(getcwd() . "/modules/{$vendor}/{$project}.phar.unloaded");

        if (count(scandir(getcwd() . "/modules/{$vendor}")) === 2) {
            @rmdir(getcwd() . "/modules/{$vendor}");
        }

        return 0;
    }
}
