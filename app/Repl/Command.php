<?php
namespace Onion\Tool\Repl;

use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Console\Interfaces\SignalAwareCommandInterface;
use Psr\Container\ContainerInterface;

class Command implements CommandInterface, SignalAwareCommandInterface
{
    private $manifest;
    private $container;

    private const WRAPPING_KEYWORDS = [
        'echo',
        'function',
        'class',
        'interface',
        'trait',
    ];

    private const BALANCE_DELIMITER_PAIRS = [
        '(' => ')',
        '{' => '}',
        '[' => ']',
        '<' => '>',
    ];

    public function __construct(Manifest $manifest, ContainerInterface $container)
    {
        $this->manifest = $manifest;
        $this->container = $container;
    }

    public function trigger(ConsoleInterface $console): int
    {
        $console->writeLine("%text:cyan%Onion CLI Tool {$this->manifest->getVersion()}");
        $__vars = [];
        $__code = '';
        $balanced = true;
        while (true) {
            try {
                $result = (function(ConsoleInterface $console, ContainerInterface $container) use (&$__vars, &$__code, &$balanced) {
                    $__code .= $console->prompt($balanced ? '%text:yellow%$>' : '%text:yellow%...');
                    $balanced = $this->checkBalance($__code);

                    if (!$balanced) {
                        return;
                    }

                    $returns = true;
                    foreach (static::WRAPPING_KEYWORDS as $keyword) {
                        if (stripos($__code, $keyword) === 0) {
                            $returns = false;
                        }
                        unset($keyword);
                        if (!$returns) {

                            break;
                        }
                    }

                    if ($returns) {
                        $__code = "return {$__code};";
                    }

                    ob_start();
                    extract($__vars, EXTR_SKIP);
                    unset($__vars, $returns);
                    if ($balanced) {
                        $result = eval(stripslashes("{$__code};"));
                        $__code = '';
                    }

                    $output = ob_get_clean();
                    if ($output !== '') {
                        $console->writeLine("  " . $output);
                    }
                    $__vars = get_defined_vars();

                    if ($result !== null) {
                        return $this->handleResult($result);
                    }
                })($console, $this->container);

                if ($result !== '' && $result !== null) {
                    $console->writeLine('%text:yellow%=> %end%' . $result);
                }
            } catch (\Throwable $ex) {
                $console->writeLine("\t%text:red%{$ex->getMessage()}");
            }
        }

        return 0;
    }

    public function exit(ConsoleInterface $console, string $signal): void
    {

    }

    private function handleResult($result): string
    {
        if (is_bool($result)) {
            return '%text:bold-white%' . ($result ? 'true' : 'false');
        }

        if (is_object($result)) {
            $class = get_class($result);
            return "%text:italic-blue%\\{$class}%end%%text:cyan% (#" . (array_search($class, get_declared_classes())+1) . ')';
        }

        if (is_array($result)) {
            $handler = function (array $items, int $indent = 1) use (&$handler) {
                $output = "";
                foreach ($items as $key => $value) {
                    if (is_string($key)) {
                        $key = "'{$key}'";
                    }

                    $output .= str_repeat(" ", $indent*4) . "[%text:yellow%{$key}%end%] => ";
                    if (is_array($value)) {
                        $output .= trim($handler($value, $indent+1)). ",\n";
                    } else {
                        $output .= $this->handleResult($value) . "%end%,\n";
                    }
                }

                if ($output !== '') {
                    $output = implode("\n", [
                        '[',
                        trim($output, "\n"),
                        str_repeat(" ", ($indent-1)*4) . ']',
                    ]);
                } else {
                    $output = "[{$output}]";
                }

                return $output;
            };

            return PHP_EOL . $handler($result);
        }

        return "%text:green%'{$result}'";
    }

    private function checkBalance(string $code)
    {

        $chars = str_split($code);
        $balance = (preg_match_all('~(?:(\"|\')+[^\"\']*)~i', $code) % 2) === 0 ? 0 : 1;

        foreach ($chars as $char) {
            if (isset(static::BALANCE_DELIMITER_PAIRS[$char])) {
                $balance++;
            }

            if (in_array($char, static::BALANCE_DELIMITER_PAIRS)) {
                $balance--;
            }
        }

        return $balance === 0;
    }
}
