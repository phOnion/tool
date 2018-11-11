<?php declare(strict_types=1);
namespace Onion\Tool\Compile;

use Onion\Cli\Config\Loader;
use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;

class Command implements CommandInterface
{
    /** @var Loader $configLoader */
    private $configLoader;

    public function __construct(Loader $configLoader)
    {
        $this->configLoader = $configLoader;
    }

    public function trigger(ConsoleInterface $console): int
    {
        $console->writeLine("%text:cyan%Compiling files");
        $configs = $this->configLoader->getConfigurations(
            $console->getArgument('environment', 'dev'),
            $console->getArgument('config-dir')
        );

        $template = '<?php return new \Onion\Framework\Dependency\Container(' . var_export($configs, true) . ');';
        file_put_contents(getcwd() . '/container.generated.php', $template);

        return 0;
    }
}
