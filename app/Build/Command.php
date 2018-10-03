<?php declare(strict_types=1);
namespace App\Build;

use Onion\Cli\Manifest\Loader;
use Onion\Cli\SemVer\Version;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Phar;
use Onion\Framework\Console\Progress;

class Command implements CommandInterface
{
    private const IGNORE_FILES = [
        '.gitignore',
        '.onionignore',
    ];

    private const COMPRESSION_MAP = [
        'gz' => \Phar::GZ,
        'bz2' => \Phar::BZ2,
    ];

    private const SIGNATURE_MAP = [
        'sha512' => \Phar::SHA512,
        'sha256' => \Phar::SHA256,
        'sha1' => \Phar::SHA1,
        'openssl' => \Phar::OPENSSL,
    ];

    /** @var Loader  */
    private $loader;
    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }

    public function trigger(ConsoleInterface $console): int
    {
        $manifest = $this->loader->getManifest();
        $console->writeLine('%text:cyan%Building package');

        $version = new Version($manifest->getVersion());

        $location = realpath($console->getArgument('location', getcwd()));
        $file = $console->getArgument('filename', strtr($manifest->getName(), ['/' => '_']));

        $filename = "{$location}/{$file}.phar";
        $phar = new \Phar($filename);

        $ignorePattern = $this->compileIgnorePattern(getcwd());
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(getcwd())
        );

        $iterator = new \RegexIterator($iterator, $ignorePattern, \RegexIterator::MATCH, \RegexIterator::USE_KEY);

        $count = iterator_count($iterator);
        $progressBar = new Progress(
            ($count > 64 ? 64 : $count),
            iterator_count($iterator),
            ['=', '#']
        );
        $progressBar->setFormat(
            '[%text:green%{buffer}%end%] (%text:yellow%{progress}%end%/%text:cyan%{steps}%end%)'
        );
        $console->writeLine('%text:cyan%Collecting package files');

        $iterator->rewind();
        foreach ($iterator as $item) {
            $progressBar->increment(1);
            if ($item->isDir()) {
                continue;
            }

            $phar->addFile($item->getRealPath(), $item->getPathName());
            $progressBar->display($console);
        }
        $console->writeLine('');

        $extension = '';
        $compression = strtolower($console->getArgument('compression', 'none'));
        if ($compression !== 'none') {
            $console->writeLine(
                "%text:cyan%Compressing using {$compression}"
            );

            if (!isset(self::COMPRESSION_MAP[$compression])) {
                throw new \InvalidArgumentException(
                    "Supplied compression algorithm '{$compression}' is invalid"
                );
            }

            if (!$phar->canCompress(self::COMPRESSION_MAP[$compression])) {
                throw new \RuntimeException("Can't compress package using '{$compression}'");
            }
            $extension = ".{$compression}";
            if (file_exists("{$filename}{$extension}")) {
                unlink("{$filename}{$extension}");
            }

            $phar->compress(self::COMPRESSION_MAP[$compression]);
        }

        $signature = strtolower($console->getArgument('signature', 'sha512'));
        if (!isset(self::SIGNATURE_MAP[$signature])) {
            throw new \InvalidArgumentException(
                "Supplied signature algorithm '{$signature}' is not supported"
            );
        }

        $algo = self::SIGNATURE_MAP[$signature];
        $key = null;
        $keyLocation = null;
        if ($signature === 'ssl') {
            $keyLocation = realpath($console->prompt('Path to private SSL key'));

            if (!$keyLocation) {
                $console->writeLine('%text:red%Private key not found');
                return 1;
            }

            $key = openssl_get_privatekey(file_get_contents($keyLocation));
        }

        $console->writeLine(
            "%text:cyan%Signature algorithm set to {$signature} " . ($key !== null ? "({$keyLocation})" : '')
        );
        $phar->setSignatureAlgorithm($algo, $key);
        unset($phar);
        if ($extension !== '') {
            unlink($filename);
        }

        $size = number_format(filesize("{$filename}{$extension}")/pow(1024, 2), 2, '.', ',');
        $console->writeLine("%text:bold-green%Build completed, size {$size}MB");
        return 0;
    }

    private function compileIgnorePattern(string $baseDir): string
    {
        $ignored = [];
        foreach (self::IGNORE_FILES as $file) {
            if (!file_exists(getcwd() . "/{$file}")) {
                continue;
            }

            $ignored = array_merge($ignored, array_map(function ($line) {
                return trim(preg_replace('#/$#', '', $line));
            }, file(getcwd() . "/{$file}")));
        }

        $ds = DIRECTORY_SEPARATOR;
        $list = implode('|', $ignored);
        return str_replace(['/', '\\', '.'], [$ds, '\\\\', '\.'], "#^{$baseDir}{$ds}(?!{$list}){$ds}?#iU");
    }
}
