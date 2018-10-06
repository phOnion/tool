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
        $cli = $manifest->getIndex('cli');
        if (!$manifest->getIndex('cli')) {
            $console->writeLine('%text:red%Missing CLI index file');
            return 1;
        }

        $web = $manifest->getIndex('web');

        $phar->setDefaultStub(
            $cli->getFile(),
            $web ? $web->getFile() : null
        );

        $ignorePattern = $this->compileIgnorePattern(getcwd());
        $iterator = new \RegexIterator(new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                getcwd(),
                \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
            )
        ), $ignorePattern, \RegexIterator::MATCH, \RegexIterator::USE_KEY);

        $phar->startBuffering();
        $phar->buildFromIterator(
            $iterator,
            getcwd()
        );

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

            $phar->compressFiles(self::COMPRESSION_MAP[$compression]);
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
        $phar->stopBuffering();

        $size = number_format(filesize("{$filename}")/pow(1024, 2), 2, '.', ',');
        $console->writeLine("%text:bold-green%Build completed, size {$size}MB");
        return 0;
    }

    private function compileIgnorePattern(string $baseDir): string
    {
        $ignored = [];
        if (file_exists(getcwd() . "/.onionignore")) {
            $ignored = array_merge($ignored, array_map(function ($line) {
                return trim(preg_replace('#/$#', DIRECTORY_SEPARATOR, $line));
            }, file(getcwd() . "/.onionignore")));
        }

        $ds = DIRECTORY_SEPARATOR;
        $list = implode('|', $ignored);
        return str_replace(['/', '\\', '.'], [$ds, '\\\\', '\.'], "#^{$baseDir}{$ds}(?!{$list}){$ds}?#iU");
    }
}
