<?php declare(strict_types=1);
namespace Onion\Tool\Compile;

use Onion\Cli\Autoload\ComposerCollector;
use Onion\Cli\RouteCompiler\Compiler;
use Onion\Framework\Common\Config\Loader;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class Command implements CommandInterface
{
    private const TEMPLATE = [
        '%s',
        'use \Onion\Framework\Common\Config\Container as Config;',
        'use \Onion\Framework\Dependency\Container;',
        '',
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
    /** @var Loader $configLoader */
    private $configLoader;

    /** @var Compiler $compiler */
    private $compiler;

    public function __construct(Loader $configLoader, Compiler $compiler)
    {
        $this->configLoader = $configLoader;
        $this->compiler = $compiler;
    }

    public function trigger(ConsoleInterface $console): int
    {
        $console->writeLine("%text:cyan%Compiling configurations ");
        $configs = $this->configLoader->loadDirectory(
            (string) $console->getArgument('environment', 'dist'),
            (string) $console->getArgument('config-dir', getcwd())
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

        $routes = $this->compiler->compileDir(getcwd() . '/app/');

        if (!empty($routes)) {
            file_put_contents(
                getcwd() . '/routes.generated.php',
                '<?php return ' . var_export(['routes' => $routes], true) . ';'
            );
        }

        return 0;
    }

    private function getAutoloadClasses(string $dir, bool $debug = false)
    {
        return (new ComposerCollector($dir))
            ->resolve(true, $debug);
    }
}
