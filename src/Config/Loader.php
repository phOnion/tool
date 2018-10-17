<?php declare(strict_types=1);
namespace Onion\Cli\Config;

use Onion\Framework\Collection\CallbackCollection;
use function Onion\Framework\merge;
use Onion\Cli\Config\Reader\ReaderInterface;

class Loader
{
    private $readers = [];
    private $directory = [];

    public function __construct($configDirectory)
    {
        $this->directory = $configDirectory;
    }

    public function registerReader(array $extensions, ReaderInterface $reader): void
    {
        foreach ($extensions as $extension) {
            $this->readers[$extension] = $reader;
        }
    }

    public function getConfigurations(string $environment): array
    {
        $iteratorOptions = \RecursiveDirectoryIterator::FOLLOW_SYMLINKS | \RecursiveDirectoryIterator::SKIP_DOTS;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, $iteratorOptions)
        );

        $registeredExtensions = array_keys($this->readers);
        $iterator = new \CallbackFilterIterator(
            $iterator,
            function (\SplFileInfo $item) use ($registeredExtensions, $environment) {
                return in_array($item->getExtension(), $registeredExtensions) &&
                    (
                        stripos($item->getFilename(), ".{$environment}.") !== false ||
                        stripos($item->getFilename(), '.global.') !== false ||
                        stripos($item->getFilename(), '.local.') !== false
                    );
            });

        $configuration = [];
        foreach ($iterator as $item) {
            if (!isset($this->readers[$item->getExtension()])) {
                throw new \RuntimeException("No reader registered for extension '{$item->getExtension()}'");
            }

            $configuration = merge($configuration, $this->readers[$item->getExtension()]->parseFile(
                $item->getRealPath()
            ));
        }

        return $configuration;
    }
}
