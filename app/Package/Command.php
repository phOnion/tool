<?php declare(strict_types=1);
namespace Onion\Tool\Package;

use Onion\Cli\Autoload\ComposerCollector;
use Onion\Cli\Manifest\Loader;
use Onion\Cli\SemVer\MutableVersion;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Phar;

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

    /** @var Loader $loader */
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


        $location = realpath($console->getArgument('location', getcwd() . '/build'));
        if (!is_dir($location)) {
            mkdir($location, 0777, true);
        }
        $file = strtr($manifest->getName(), ['/' => '_']);
        $filename = "{$location}/{$file}-{$version}.phar";
        if (file_exists($filename)) {
            $console->writeLine('%text:yellow%Removing existing artefact');
            unlink($filename);
        }

        $console->writeLine('%text:cyan%Building package');
        $phar = new \Phar($filename);
        $standalone = $console->getArgument('standalone', false);

        if ($standalone) {
            $phar->setStub($this->getStub($standalone));
        }

        if (!$standalone) {
            $phar->addFile($this->getModuleEntrypoint(), 'entrypoint.php');
            $phar->setStub('<?php echo "Can\'t be used directly"; __HALT_COMPILER();"');
        }

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

    private function getModuleEntrypoint(): string
    {
        $file = "/data/module.php";
        if (file_exists(getcwd() . $file)) {
            return getcwd() . $file;
        }

        if (file_exists(Phar::running(true) . $file)) {
            return Phar::running(true) . $file;
        }

        $temp = tempnam(sys_get_temp_dir(), 'module');
        file_put_contents($temp, '<?php echo "No module file found";');

        return $temp;
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
                '\\' => '/',
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
