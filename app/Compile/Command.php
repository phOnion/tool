<?php

declare(strict_types=1);

namespace Onion\Tool\Compile;

use Onion\Cli\Autoload\ComposerCollector;
use Onion\Framework\Config\Loader;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class Command implements CommandInterface
{
    private const TEMPLATE = [
        '%s',
        'use \Onion\Framework\Config\Container as Config;',
        'use \Onion\Framework\Dependency\Container;',
        '',
        '$config = new Config(%s);',
        '$container = new Container([',
        '    "factories" => $config->has("factories") ? $config->get("factories") : [],',
        '    "invokables" => $config->has("invokables") ? $config->get("invokables") : [],',
        ']);',
        '',
        'return [$config, $container];',
        ''
    ];

    public function __construct(
        private readonly Loader $configLoader
    ) {
    }

    public function trigger(ConsoleInterface $console): int
    {
        $console->writeLine("<color text='cyan'>Compiling configurations </color>");
        $configs = $this->configLoader->loadDirectory(
            (string) $console->getArgument('environment', 'dist'),
            (string) $console->getArgument('config-dir', './config')
        );

        $autoload = $this->getAutoloadClasses(
            getcwd(),
            (bool) $console->getArgument('dev', false)
        );
        $result = var_export($autoload, true);

        file_put_contents(getcwd() . '/autoload.generated.php', "<?php return {$result};");

        $file = getcwd() . '/container.generated.php';
        file_put_contents($file, sprintf(
            implode("\n", static::TEMPLATE),
            file_get_contents((\Phar::running(true) ?: getcwd()) . '/data/loader.php'),
            var_export($configs, true)
        ));

        return 0;
    }

    private function getAutoloadClasses(string $dir, bool $debug = false)
    {
        return (new ComposerCollector($dir))
            ->resolve(true, $debug);
    }
}
