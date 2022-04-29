<?php

declare(strict_types=1);

namespace Onion\Tool\Package;

use AppendIterator;

use Onion\Cli\Autoload\ComposerCollector;
use Onion\Cli\Manifest\Loader;
use Onion\Cli\SemVer\MutableVersion;
use Onion\Framework\Console\Interfaces\CommandInterface;
use Onion\Framework\Console\Interfaces\ConsoleInterface;
use Onion\Framework\Console\Interfaces\SignalAwareCommandInterface;
use Onion\Tool\Package\Service\Packer;

use Phar;

class Command implements CommandInterface, SignalAwareCommandInterface
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
        $version = new MutableVersion($manifest->getVersion());
        $manifest = $manifest->setVersion(
            $this->buildVersionString($version)
        );
        $this->loader->saveManifest(getcwd(), $manifest);

        $filename = $this->getOutputLocation(
            (string) $console->getArgument('location', getcwd() . '/build'),
            $manifest->getName(),
            $manifest->getVersion()
        );
        $standalone = (bool) $console->getArgument('standalone');
        $debug = (bool) $console->getArgument('debug');

        $packer = new Packer($filename, file(getcwd() . '/.onionignore'));
        foreach ($this->getAggregatedDirectories($standalone, $debug) as $directory) {
            $packer->addDirectory(getcwd() . DIRECTORY_SEPARATOR . $directory);
        }
        $console->write('<color text="cyan">Building package</color>');
        $console->overwrite();
        $phar = $packer->pack(getcwd(), $console);

        $phar->addFile($this->getModuleEntrypoint(), 'entrypoint.php');
        $phar->setStub('<?php echo "Can\'t be used directly"; __HALT_COMPILER();"');
        if ($standalone) {
            $phar->setStub($this->getStub());
            $phar->delete('entrypoint.php');
        }

        $compression = strtolower($console->getArgument('compression', 'none'));
        if ($compression !== 'none') {
            $console->overwrite("<color text=\"cyan\">Compressing using {$compression}</color>");
            $mode = self::COMPRESSION_MAP[$compression] ?? null;
            if ($mode === null || !$phar->canCompress($mode)) {
                throw new \InvalidArgumentException("Compression using '{$compression}' not possible");
            }

            $phar->compressFiles($mode);
        }

        $signature = strtolower($console->getArgument('signature', 'sha256'));
        if (!isset(self::SIGNATURE_MAP[$signature])) {
            throw new \InvalidArgumentException("Unknown signature algorithm '{$signature}'");
        }

        $phar->setSignatureAlgorithm(self::SIGNATURE_MAP[$signature]);
        $phar->setMetadata([
            'version' => $version->getBaseVersion(),
            'standalone' => $standalone,
            'debug' => $console->getArgument('debug', false),
        ]);
        unset($phar);


        $size = number_format(filesize($filename) / pow(1024, 2), 3, '.', ',');
        $console->overwrite("<color text='green' decoration='bold'>Build completed, size {$size}MB</color>");

        return 0;
    }

    public function exit(ConsoleInterface $console, string $signal): void
    {
        $console->writeLine('<color text="yellow">Cleaning up</color>');
        $manifest = $this->loader->getManifest();
        $version = new MutableVersion($manifest->getVersion());

        $file = strtr($manifest->getName(), ['/' => '_']);
        $filename = "{$console->getArgument('location', getcwd() . '/build')}/{$file}-{$version}.phar";
        if ($version->hasBuild()) {
            $version->setBuild((string) ($version->getBuild() - 1));
        }

        $console->writeLine('<color text="yellow">Rolling back version</color>');
        $this->loader->saveManifest(getcwd(), $manifest->setVersion($this->buildVersionString($version)));

        if (file_exists($filename)) {
            $console->writeLine('<color text="yellow">Removing incomplete artifact</color>');
            unlink($filename);
        }
    }

    private function buildVersionString(MutableVersion $version): string
    {
        if ($version->hasBuild()) {
            $version->setBuild(str_pad(
                (string) ($version->getBuild() + 1),
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
        file_put_contents($temp, '<?php echo "No module file found"; __HALT_COMPILER();');

        return $temp;
    }

    private function getVendorClassMap(bool $standalone, bool $includeDev = false): iterable
    {
        return (new ComposerCollector(getcwd()))
            ->resolve($standalone, $includeDev);
    }

    private function getAggregatedDirectories(bool $executable, bool $debug): iterable
    {
        $autoload = $this->getVendorClassMap($executable, $debug);
        $iterator = new AppendIterator();
        $iterator->append(new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($autoload['psr-4'] ?? [])
        ));
        $iterator->append(new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($autoload['psr-0'] ?? [])
        ));

        return $iterator;
    }

    private function getOutputLocation(string $location, string $name, string $version): string
    {
        $dir = realpath($location);
        if (!$dir || !is_dir($location)) {
            mkdir($location, 0777, true);
        }
        $location = $dir;

        $file = strtr($name, ['/' => '_']);
        $filename = "{$location}/{$file}-{$version}.phar";
        if (file_exists($filename)) {
            unlink($filename);
        }

        return $filename;
    }
}
