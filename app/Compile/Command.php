<?php declare(strict_types=1);
namespace Onion\Tool\Compile;

use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Common\Config\Loader;

class Command implements CommandInterface
{
    private const TEMPLATE = [
        '<?php',
        'use \Onion\Framework\Common\Config\Container as Config;',
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
    /** @var Loader $configLoader */
    private $configLoader;

    public function __construct(Loader $configLoader)
    {
        $this->configLoader = $configLoader;
    }

    public function trigger(ConsoleInterface $console): int
    {
        $console->writeLine("%text:cyan%Compiling configurations ");
        $configs = $this->configLoader->loadDirectory(
            $console->getArgument('environment', 'dist'),
            $console->getArgument('config-dir', getcwd())
        );

        $file = getcwd() . '/container.generated.php';
        file_put_contents($file, sprintf(
            implode("\n", static::TEMPLATE),
            var_export($configs, true)
        ));

        if ($console->getArgument('verbose')) {
            $console->writeLine(
                "%text:green%Done\t %text:blue%{$this->formatBytes((string) filesize($file), 3)}"
            );
        }


        return 0;
    }

    private function formatBytes(string $bytes, int $decimals = 2)
    {
            $size = array('B','KB','MB','GB','TB','PB','EB','ZB','YB');
            $factor = floor((strlen($bytes) - 1) / 3);
            return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }
}
