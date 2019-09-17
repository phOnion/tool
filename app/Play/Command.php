<?php
namespace Onion\Tool\Play;

use function Onion\Framework\Loop\coroutine;

use Onion\Cli\Watcher\Watcher;
use Onion\Framework\Common\Config\Loader;
use Onion\Framework\Console\Interfaces\CommandInterface;

use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Loop\Interfaces\ResourceInterface;
use Onion\Framework\Loop\Timer;
use Onion\Framework\Process\Process;
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
            yield from $this->runCommands(
                $console,
                $definition,
                $config,
                $stage
            );

            if (isset($definition['watch'])) {
                coroutine(function (
                    int $interval,
                    ConsoleInterface $console,
                    array $definition,
                    array $config,
                    string $stage
                ) {
                    $handler = function () use ($console, $definition, $config, $stage) {
                        coroutine(function ($console, $definition, $config, $stage) {
                            try {
                                yield from $this->runCommands(
                                    $console->withArgument('quiet', true),
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
                    $console->writeLine("%text:cyan%Watching for changes");

                    yield Timer::interval(function (Watcher $watcher, array $definition) {
                        foreach ((array) $definition['watch'] as $dir) {
                            try {
                                $watcher->addDirectory($dir);
                            } catch (\Throwable $ex) {
                                // Prevent crashes
                            }
                        }
                        usleep(250000);
                    }, $interval, [$watcher, $definition]);
                }, [
                    $console->getArgument('interval', 1000),
                    $console,
                    $definition,
                    $config,
                    $stage
                ]);
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
        if (isset($definition['require'])) {
            $deps = (array) $definition['require'];
            foreach ($deps as $dep) {
                $process = $this->execProcess(
                    Phar::running(false) ?: "php {$_SERVER['PHP_SELF']}",
                    ['play', $dep]
                );

                do {
                    $console->write($process->read(1024));
                    if (!$process->isAlive()) {
                        break;
                    }
                    usleep(250000);
                    yield $process->wait();

                } while ($process->isRunning());

                if ($process->getExitCode() !== 0) {
                    throw new \RuntimeException("Required stage {$dep} failed");
                }
            }
        }

        $console->writeLine("\n\t%text:bold-white%STAGE %text:green%{$stage}");
        foreach ($config['stages'][$stage]['steps'] ?? [] as $index => $step) {
            $number = $index+1;
            $console->write("%text:cyan%Step %text:yellow%#{$number}");
            $process = $this->execProcess(
                $step['command'],
                $step['args'] ?? [],
                $step['cwd'] ?? getcwd(),
                $step['env'] ?? []
            );

            while ($process->isRunning()) {
                yield $process->wait();
            }

            if ($process->getExitCode() !== 0) {
                $console->writeLine(
                    "\t\t%text:bold-red%FAIL (Error code {$process->getExitCode()})"
                );
                throw new \RuntimeException("Build of {$stage} failed");
            }
            $console->writeLine("\t\t%text:bold-green%DONE");
        }
    }
}
