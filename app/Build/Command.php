<?php declare(strict_types=1);
namespace Onion\Tool\Build;

use Onion\Cli\Manifest\Loader;
use Onion\Cli\SemVer\MutableVersion;
use Onion\Cli\SemVer\Version;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Console\Progress;
use Onion\Tool\Compile\Command as Compile;
use Phar;
use Onion\Cli\Autoload\ComposerCollector;

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
    /** @var Compile */
    private $compileCommand;
    public function __construct(Loader $loader, Compile $command)
    {
        $this->loader = $loader;
        $this->compileCommand = $command;
    }

    public function trigger(ConsoleInterface $console): int
    {
        $manifest = $this->loader->getManifest();
        $version = new MutableVersion($console->getArgument('version', $manifest->getVersion()));

        $manifest = $manifest->setVersion(
            $this->buildVersionString($console, $version)
        );
        $this->loader->saveManifest(getcwd(), $manifest);
        $this->compileCommand->trigger($console);


        $location = realpath($console->getArgument('location', getcwd() . '/build'));
        if (!is_dir($location)) {
            mkdir($location, 0777, true);
        }
        $file = $console->getArgument('filename', strtr($manifest->getName(), ['/' => '_']));
        $filename = "{$location}/{$file}.phar";
        if (file_exists($filename)) {
            $console->writeLine('%text:yellow%Removing existing artefact');
            unlink($filename);
        }

        $console->writeLine('%text:cyan%Building package');
        $phar = new \Phar($filename);
        $standalone = $console->getArgument('standalone', false);
        $phar->setStub($this->getStub($standalone));

        $temp = tempnam(sys_get_temp_dir(), 'autoload');
        $files = $this->getVendorClassMap($console->getArgument('debug', false));
        $result = var_export($files, true);
        file_put_contents($temp, "<?php return {$result};");
        $phar->addFile($temp, 'autoload.php');

        $iterator = $this->getDirectoryIterator(getcwd(), $standalone);

        $phar->startBuffering();
        $phar->buildFromIterator($iterator, getcwd());

        $compression = strtolower($console->getArgument('compression', 'none'));
        if ($compression !== 'none') {
            $console->writeLine("%text:cyan%Compressing using {$compression}");

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
            throw new \InvalidArgumentException("Unknown signature algorithm '{$signature}'");
        }

        $algo = self::SIGNATURE_MAP[$signature];

        $console->writeLine(
            "%text:cyan%Signature algorithm set to {$signature} "
        );

        $phar->setSignatureAlgorithm($algo);
        $phar->setMetadata([
            'version' => $version->getBaseVersion(),
            'standalone' => $standalone,
            'debug' => $console->getArgument('debug', false),
        ]);
        $phar->stopBuffering();

        $size = number_format(filesize("{$filename}")/pow(1024, 2), 3, '.', ',');
        $console->writeLine("%text:bold-green%Build completed, size {$size}MB");
        return 0;
    }

    private function compileIgnorePattern(): iterable
    {
        return array_map(function ($line) {
            return trim(strtr($line, [
                '/' => DIRECTORY_SEPARATOR,
                '*' => '',
            ]));
        }, file(getcwd() . "/.onionignore"));
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

    private function getStub(): string
    {
        $file = "/data/stub.php";
        if (file_exists(getcwd() . $file)) {
            return file_get_contents(getcwd() . $file);
        }

        if (file_exists(Phar::running(true) . $file)) {
            return file_get_contents(Phar::running(true) . $file);
        }

        return \Phar::running() !== '' ?
            (new \Phar(\Phar::running()))->getStub() : '<?php echo "No stub"; __HALT_COMPILER();';
    }

    private function getDirectoryIterator($dir, bool $standalone = false): \Traversable
    {
        $patterns = $this->compileIgnorePattern();
        if ($standalone) {
            $patterns[] = 'composer.*';
        }

        return new \CallbackFilterIterator(new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                "$dir/",
                \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS
            )
        ), function ($item, $key) use ($patterns, $dir) {
            $key = strtr($key, [
                $dir => '',
            ]);
            foreach ($patterns as $pattern) {
                if (strpos($key, "{$pattern}") !== false) {
                    return false;
                }
            }

            return true;
        });
    }

    private function getVendorClassMap($file, bool $includeDev = false): iterable
    {
        return (new ComposerCollector(getcwd()))
            ->resolve($includeDev);
    }
}
