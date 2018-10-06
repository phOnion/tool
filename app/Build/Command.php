<?php declare(strict_types=1);
namespace Onion\Tool\Build;

use Onion\Cli\Manifest\Loader;
use Onion\Cli\SemVer\Version;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Phar;
use Onion\Framework\Console\Progress;
use Onion\Cli\SemVer\MutableVersion;

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
        $version = new MutableVersion($console->getArgument('version', $manifest->getVersion()));

        $this->loader->saveManifest(getcwd(), $manifest->setVersion(
            $this->buildVersionString($console, $version)
        ));

        $console->writeLine('%text:cyan%Building package');
        $location = realpath($console->getArgument('location', getcwd()));
        $file = $console->getArgument('filename', strtr($manifest->getName(), ['/' => '_']));
        $filename = "{$location}/{$file}.phar";

        $phar = new \Phar($filename);
        $cli = $manifest->getIndex('cli');
        if (!$cli) {
            $console->writeLine('%text:red%Missing CLI index file');
            return 1;
        }

        $web = $manifest->getIndex('web');
        // file_put_contents('stub.php', $phar->getStub());
        $phar->setStub(strtr(file_get_contents('data/stub.php'), [
            '__WEB_STUB__' => $web ? "'{$web->getFile()}'" : false,
            '__CLI_STUB__' => "'{$cli->getFile()}'",
        ]));

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
        $phar->setMetadata([
            'version' => $version,
        ]);
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

    private function buildVersionString(ConsoleInterface $console, MutableVersion $version): string
    {
        if ($version->hasBuild()) {
            $version->setBuild(str_pad(
                (string) ($version->getBuild()+1),
                strlen($version->getBuild()),
                '0',
                STR_PAD_LEFT
            ));
        }

        if ($console->hasArgument('bump')) {
            switch (strtolower($console->getArgument('bump'))) {
                case 'major':
                    $version->setMajor($version->getMajor()+1);
                    $version->setMinor(0);
                    $version->setFix(0);
                    break;
                case 'minor':
                    $version->setMinor($version->getMinor()+1);
                    $version->setFix(0);
                    break;
                case 'fix':
                    $version->setFix($version->getFix()+1);
                    break;
                default:
                    $console->writeLine(
                        "%text:red%Unknown `bump` type {$console->getArgument('bump')}"
                    );
                    return 1;
                    break;
            }
        }

        if ($console->hasArgument('pre')) {
            $version->setPreRelease($console->getArgument('pre'));
        }

        return (string) $version;
    }
}
