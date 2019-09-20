<?php
namespace Onion\Tool\Play;

use function Onion\Framework\Loop\coroutine;

use Onion\Cli\Watcher\Watcher;
use Onion\Framework\Common\Config\Loader;
use Onion\Framework\Console\Components\Progress;
use Onion\Framework\Console\Interfaces\CommandInterface;

use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Loop\Coroutine;
use Onion\Framework\Loop\Timer;
use Onion\Framework\Process\Process;
use Onion\Framework\Promise\FulfilledPromise;
use Onion\Framework\State\Flow;
use Onion\Framework\State\Transition;
use Phar;

class Command implements CommandInterface
{
    private $loader;

    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }

    public function trigger(ConsoleInterface $console): int
    {
        $config = $console->getArgument('config');
        $configLocation = getcwd() . '/' . $config;

        if (!file_exists($configLocation)) {
            $console->writeLine("%text:red%Provided config '{$configLocation}' does not exist");
            return 1;
        }

        coroutine(function (array $config, ConsoleInterface $console) {
            $stage = $console->getArgument('stage', 'build');
            if (!isset($config['stages'][$stage])) {
                throw new \RuntimeException("Stage '{$stage}' does not exist");
            }

            $definition = &$config['stages'][$stage];
            try {
                yield from $this->runCommands(
                    $console,
                    $definition,
                    $config,
                    $stage
                );


                if (isset($definition['watch']) && !$console->getArgument('no-watch')) {
                    $handler = function () use ($console, $definition, $config, $stage) {
                        coroutine(function ($console, $definition, $config, $stage) {
                            try {
                                yield from $this->runCommands(
                                    $console,
                                    $definition,
                                    $config,
                                    $stage
                                );
                            } catch (\Throwable $ex) {
                                //
                            }

                        }, [$console, $definition, $config, $stage]);
                    };
                    $flow = new Flow('watcher', 'initial');
                    $flow->addTransition(new Transition('initial', 'change', $handler));
                    $flow->addTransition(new Transition('change', 'change', $handler));
                    $flow->addTransition(new Transition('change', 'delete'));

                    $watcher = new Watcher($flow);

                    yield Timer::interval(function (Watcher $watcher, array $definition) {
                        foreach ((array) $definition['watch'] as $dir) {
                            try {
                                $watcher->addDirectory($dir);
                            } catch (\Throwable $ex) {
                                // Prevent crashes
                            }
                        }
                    }, (int) $console->getArgument('interval', 1000), [$watcher, $definition]);
                }
            } catch (\Throwable $ex) {
                exit(1);
            }
        }, [$this->loader->loadFile($configLocation), $console]);

        return 0;
    }

    private function execProcess($command, $args, $cwd = null, $env = []): Process
    {
        $process = Process::exec($command, $args, $env, $cwd);
        $process->unblock();

        return $process;
    }

    private function runCommands($console, $definition, $config, $stage)
    {
        // microtime(true);
        if (isset($definition['require'])) {
            $deps = (array) $definition['require'];
            foreach ($deps as $dep) {
                $console->write("%text:cyan%Executing dependency task %text:blue%{$dep}");

                $process = $this->execProcess(
                    Phar::running(false) ?: "php {$_SERVER['PHP_SELF']}",
                    ['play', $dep, '--no-watch']
                );

                do {
                    yield $process->wait();

                    $process->read(1024);
                    if (!$process->isAlive()) {
                        break;
                    }
                } while ($process->isRunning());

                if ($process->getExitCode() !== 0) {
                    $console->writeLine("%text:red%Required stage '%text:bold-red%{$dep}%text:red%' failed");
                    throw new \RuntimeException('Required step failed');
                }
                $console->writeLine("%text:bold-green%\t\tDONE");
            }
        }

        $console->write(
            "%text:cyan%Executing stage %text:green%{$stage}"
        );

        $results = [];
        foreach ($config['stages'][$stage]['steps'] ?? [] as $index => $step) {
            yield Coroutine::create(function ($console, $stage, $index, $step) use (&$results) {
                $process = $this->execProcess(
                    $step['command'],
                    $step['args'] ?? [],
                    $step['cwd'] ?? getcwd(),
                    $step['env'] ?? []
                );

                while ($process->isRunning()) {
                    yield $process->wait();
                    $process->read(1024);
                }

                if ($process->getExitCode() !== 0) {
                    $index += 1;
                    $console->writeLine(
                        "\t%text:bold-red%#{$index} FAIL (Error code {$process->getExitCode()})"
                    );
                    throw new \RuntimeException("Build of {$stage} failed");
                }

                $results[] = true;
            }, [$console, $stage, $index, $step]);
        }

        $timer = -1;
        $timer = yield Timer::interval(function ($console, $definition, $totalSteps) use (&$timer, &$results) {
            if (count($results) !== $totalSteps) {
                return;
            }
            $console->writeLine("\t\t%text:bold-green%DONE");

            yield Coroutine::kill($timer);
            if (isset($definition['watch']) && !$console->getArgument('no-watch')) {
                yield Timer::after(function (ConsoleInterface $console) {
                    yield $console->writeLine("\e[H\e[J");
                    $console->writeLine("%text:cyan%Watching for changes");
                }, 250, [$console]);
            }
        }, 250, [$console, $definition, count($config['stages'][$stage]['steps'] ?? [])]);
    }
}
