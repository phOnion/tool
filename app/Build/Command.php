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

        $manifest = $manifest->setVersion(
            $this->buildVersionString($console, $version)
        );
        $this->loader->saveManifest(getcwd(), $manifest);

        $console->writeLine('%text:cyan%Building package');
        $location = realpath($console->getArgument('location', getcwd()));
        $file = $console->getArgument('filename', strtr($manifest->getName(), ['/' => '_']));
        $filename = "{$location}/{$file}.phar";

        $phar = new \Phar($filename);
        $stub = file_exists(getcwd() . '/data/stub.php') ?
            file_get_contents(getcwd() . '/data/stub.php') : (
                \Phar::running() !== '' ?
                    (new \Phar(\Phar::running()))->getStub() : '<?php echo "No stub"; __HALT_COMPILER();'
                );
        $phar->setStub($stub);

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

        $signature = strtolower($console->getArgument('signature', 'sha256'));
        if (!isset(self::SIGNATURE_MAP[$signature])) {
            throw new \InvalidArgumentException(
                "Supplied signature algorithm '{$signature}' is not supported"
            );
        }

        $algo = self::SIGNATURE_MAP[$signature];

        $console->writeLine(
            "%text:cyan%Signature algorithm set to {$signature} "
        );

        $phar->setSignatureAlgorithm($algo);
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
        $list = '';
        $ds = DIRECTORY_SEPARATOR;

        if (file_exists(getcwd() . "/.onionignore")) {
            $ignored = array_merge($ignored, array_map(function ($line) {
                return trim(preg_replace('#/$#', DIRECTORY_SEPARATOR, $line));
            }, file(getcwd() . "/.onionignore")));

            $list = '(?!' . implode('|', $ignored) . "){$ds}?";
        }

        return str_replace(['/', '\\', '.'], [$ds, '\\\\', '\.'], "#^{$baseDir}{$ds}{$list}#iU");
    }

    private function buildVersionString(ConsoleInterface $console, MutableVersion $version): string
    {
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

        if ($version->hasBuild()) {
            $version->setBuild(str_pad(
                (string) ($version->getBuild()+1),
                strlen($version->getBuild()),
                '0',
                STR_PAD_LEFT
            ));
        }

        return (string) $version;
    }
}
