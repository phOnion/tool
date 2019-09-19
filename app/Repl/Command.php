<?php
namespace Onion\Tool\Repl;

use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Console\Interfaces\SignalAwareCommandInterface;
use Psr\Container\ContainerInterface;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

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
        $__balanced = true;
        $__history = [];

        if (function_exists('\readline_completion_function')) {
            readline_completion_function(function ($row) use (&$__vars) {
                $result = [];

                if ($row === '') {
                    return $result;
                }
                foreach ($__vars as $name => $value) {
                    if (strpos($name, $row) === 0) {
                        $result[] = $name;
                    }
                }

                $functions = new RecursiveIteratorIterator(
                    new RecursiveArrayIterator(get_defined_functions())
                );
                foreach ($functions as $name) {
                    if (strpos($name, $row) === 0) {
                        $result[] = $name . '(';
                    }
                }

                return $result;
            });
        }
        while (true) {
            try {
                $result = (function(ConsoleInterface $__console, ContainerInterface $container) use (&$__vars, &$__code, &$__balanced) {
                    $__vars['container'] = $container;
                    $__code .= $__console->prompt($__balanced ? '%text:yellow%$>' : '%text:yellow%...');
                    $__balanced = $this->checkBalance($__code);

                    if (!$__balanced) {
                        return;
                    }
                    readline_add_history($__code);

                    $__returns = true;
                    foreach (static::WRAPPING_KEYWORDS as $__keyword) {
                        if (stripos($__code, $__keyword) === 0) {
                            $__returns = false;
                        }
                        unset($__keyword);
                        if (!$__returns) {

                            break;
                        }
                    }

                    if ($__returns) {
                        $__code = "return {$__code};";
                    }

                    ob_start();
                    extract($__vars, EXTR_SKIP);
                    if ($__balanced) {
                        $__code_b = $__code;
                        $__code = '';
                        $__result = eval(stripslashes("{$__code_b};"));
                    }

                    $__output = ob_get_clean();
                    if ($__output !== '') {
                        $__console->writeLine("  " . $__output);
                    }

                    $__vars = array_filter(get_defined_vars(), function ($key) {
                        return stripos($key, '__') !== 0;
                    },  ARRAY_FILTER_USE_KEY);

                    if ($__result !== null) {
                        return $this->handleResult($__result);
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
        $balance = (preg_match_all('~(?:(\"|\')+[^\"\']*)~im', $code) % 2) === 0 ? 0 : 1;
        if ($balance !== 0) {
            return false;
        }

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
