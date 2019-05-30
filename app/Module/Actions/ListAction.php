<?php declare(strict_types=1);
namespace Onion\Tool\Module\Actions;

use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Cli\Manifest\Entities\Manifest;

class ListAction extends ShowAction implements ActionInterface
{
    public function validateModule(string $module)
    {
        return strtolower($module) === 'all' || $module === '';
    }

    public function perform(ConsoleInterface $console, string $module): int
    {
        $console->writeLine('');
        $files = glob(getcwd() . '/modules/**/*.phar');
        $numberOfModules = count($files);

        if ($numberOfModules === 0) {
            $console->writeLine('%text:cyan%No modules installed');
            return 0;
        }

        foreach ($files as $index => $file) {
            $pattern = sprintf("\t Package %s/%d", str_pad((string) ($index+1), strlen((string) ($numberOfModules)), ' '), $numberOfModules);
            $console->writeLine($pattern);
            parent::perform($console, str_replace([
                getcwd(),
                '/modules/',
                '.phar'
            ], ['', '', ''], $file));
            $console->writeLine('');
        }

        return 0;
    }
}
