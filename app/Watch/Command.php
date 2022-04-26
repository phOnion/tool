<?php

declare(strict_types=1);

namespace Onion\Tool\Watch;

use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Loop\Timer;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Command implements CommandInterface
{

    public function trigger(ConsoleInterface $console): int
    {
        $registry = [];
        Timer::interval(function () use ($console, &$registry) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $console->getArgument('directory'),
                    \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
                ),
            );

            foreach ($iterator as $file) {
                if (is_dir($file)) continue;

                $hash = hash_file('xxh3', $file);
                if (!isset($registry[$file])) {
                    $registry[$file] = $hash;
                } else if ($registry[$file] !== $hash) {
                    exec($console->getArgument('command'));
                    $registry[$file] = $hash;
                }
            }
        }, $console->getArgument('interval') * 1000);

        return 0;
    }
}
