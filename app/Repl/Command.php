<?php
namespace Onion\Tool\Repl;

use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Console\Interfaces\SignalAwareCommandInterface;
use Psr\Container\ContainerInterface;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use ReflectionObject;

use function Onion\Framework\Loop\coroutine;

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
        'use',
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
        $__imports = [];

        if (function_exists('\readline_completion_function')) {
            readline_completion_function(function ($row, $index) use (&$__vars, $console) {
                readline_info('completion_suppress_append', true);
                $info = readline_info();
                $completion = [];
                $result = [];

                if ($row === '') {
                    return $result;
                }
                if (stripos(substr($info['line_buffer'], $index-1), '$') !== false) {
                    foreach ($__vars as $name => $value) {
                        if (strpos($name, $row) === 0) {
                            $result[] = $name;
                            $completion["%text:yellow%\${$name}%end%"] = $this->handleResult($value);
                        }
                    }
                }

                if (stripos(substr($info['line_buffer'], $index-1), '$') === false && stripos(substr($info['line_buffer'], $index-2), '->') === false) {
                    $functions = new RecursiveIteratorIterator(
                        new RecursiveArrayIterator(get_defined_functions())
                    );
                    foreach ($functions as $real) {
                        $name = $real;
                        if (stripos($real, 'onion') !== false && stripos($real, '\\') !== false) {
                            $parts = explode('\\', $real);
                            $name = array_pop($parts);
                        }

                        if (strpos($name, $row) === 0) {
                            $result[] = $name . '(';
                            $reflection = new \ReflectionFunction($real);
                            $params = [];
                            foreach ($reflection->getParameters() as $param) {
                                $params[] = ($param->isOptional() ? '?' : '') . ($param->hasType() ? $param->getType() . ' ' : '') .
                                "%text:blue%\${$param->getName()}%text:blue%" . (
                                    $param->isDefaultValueAvailable() ? "=%text:green%" . $param->getDefaultValue() : ''
                                );
                            }

                            $completion["%text:yellow%{$real}(%text:cyan%".implode('%end%, %text:cyan%', $params)."%text:yellow%)"] =
                                ($reflection->hasReturnType() ? ": {$reflection->getReturnType()};" : ';');
                        }
                    }
                }

                $start = $index-2;
                while (substr($info['line_buffer'], $start, 1) !== ' ' && $start > 0) {
                    $start--;
                }

                try {
                    extract($__vars, EXTR_OVERWRITE);
                    $object = eval('return ' . substr($info['line_buffer'], $start, $index-2) . ';');
                    $reflection = new ReflectionObject($object);

                    foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                        if (stripos($method->getName(), $row) !== 0) {
                            continue;
                        }
                        $result[] = "{$method->getName()}(";

                        foreach ($method->getParameters() as $param) {
                            $params[] = ($param->isOptional() ? '?' : '') . ($param->hasType() ? $param->getType() . ' ' : '') .
                            "%text:blue%\${$param->getName()}%text:blue%" . (
                                $param->isDefaultValueAvailable() ? "=%text:green%" . $param->getDefaultValue() : ''
                            );
                        }

                        $completion["%text:yellow%{$reflection->getName()}@{$method->getName()}(%text:cyan%".implode('%end%, %text:cyan%', $params)."%text:yellow%)"] =
                            ($method->hasReturnType() ? ":{$method->getReturnType()};" : ';');
                    }
                } catch (\Throwable $ex) {
                    echo "{$ex->getMessage()}\n";
                }


                $console->writeLine("\n");
                foreach ($completion as $index => $item) {
                    $console->writeLine("{$index}{$item}");
                }

                $console->writeLine("\n");
                readline_on_new_line();
                readline_redisplay();

                return $result;
            });
        }

        coroutine(function(ConsoleInterface $__console, ContainerInterface $container) use (&$__vars, &$__code, &$__balanced, &$__imports) {
            while (true) {
                try {
                    $__vars['container'] = $container;
                    yield $__code .= $__console->prompt($__balanced ? '%text:yellow%$>' : '%text:yellow%...');
                    $__balanced = $this->checkBalance($__code);

                    if (!$__balanced) {
                        continue;
                    }

                    yield readline_add_history($__code);

                    $__returns = true;
                    foreach (static::WRAPPING_KEYWORDS as $__keyword) {
                        if (stripos($__code, $__keyword) === 0) {
                            if (stripos($__code, 'use') === 0) {
                                $__imports[] = $__code;
                            }

                            $__returns = false;
                            break;
                        }

                        yield;
                    }

                    ob_start();
                    extract($__vars, EXTR_SKIP);
                    if ($__returns) {
                        $__code = implode(";\n", $__imports) . ";\n" . "return {$__code};";
                    }

                    $__code_b = $__code;
                    $__code = '';
                    $__result = eval(stripslashes(strtr("{$__code_b};", [
                        '\\' => '\\\\',
                    ])));

                    $__output = ob_get_clean();
                    if ($__output !== '') {
                        $__console->writeLine("  " . $__output);
                    }

                    $__vars = array_filter(get_defined_vars(), function ($key) {
                        return stripos($key, '__') !== 0;
                    },  ARRAY_FILTER_USE_KEY);

                    if ($__result !== '' && $__result !== null) {
                        $__console->writeLine('%text:yellow%=> %end%' . $this->handleResult($__result));
                    }
                } catch (\Throwable $__ex) {
                    $__console->writeLine("\t%text:red%{$__ex->getMessage()}");
                }
            }
        }, [$console, $this->container]);

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

        return $balance <= 0;
    }
}
