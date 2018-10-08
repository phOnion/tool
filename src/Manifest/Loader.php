<?php declare(strict_types=1);
namespace Onion\Cli\Manifest;

use Onion\Cli\Manifest\Entities\Command;
use Onion\Cli\Manifest\Entities\Link;
use Onion\Cli\Manifest\Entities\Maintainer;
use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Cli\Manifest\Entities\Package;
use Onion\Cli\Manifest\Entities\Parameter;

class Loader
{
    /**
     * @var array
     **/
    private $entityMaps;

    public function __construct(array $manifestMap)
    {
        $this->entityMaps = $manifestMap;
    }

    private function loadManifest(?string $directory = null): array
    {
        $directory = $directory ?? getcwd();
        if (!file_exists("{$directory}/onion.json")) {
            throw new \RuntimeException("Manifest file 'onion.json' not found in current director. Did you forget to init?");
        }

        return json_decode(file_get_contents("{$directory}/onion.json"), true);
    }

    private function getSection(array $raw, string $section): ?iterable
    {
        if (!isset($this->entityMaps[$section])) {
            return null;
        }

        $result = [];
        $reflection = new \ReflectionClass($this->entityMaps[$section]);
        $constructor = $reflection->getConstructor();
        $arguments = array_map(function (\ReflectionParameter $param) {
            return $param->getName();
        } ,$constructor->getParameters());

        foreach ($raw[$section] as $definition) {
            $args = [];
            foreach ($arguments as $position => $name) {
                if (!isset($definition[$name])) {
                    continue;
                }

                $args[$position] = $definition[$name];
            }

            $result[] = $reflection->newInstanceArgs($args);
        }

        return $result;
    }

    public function getManifest(string $directory = null): Manifest
    {
        $raw = $this->loadManifest($directory);

        $manifest = new Manifest(
            $raw['name'] ?? '',
            $raw['version'] ?? '0.0.0',
            $this->getSection($raw, 'links')
        );

        return $manifest->withCommands($this->getSection($raw, 'commands'))
            ->withRepositories($this->getSection($raw, 'repositories'));
    }

    public function manifestExists(string $directory = null): bool
    {
        $directory = $directory ?? getcwd();
        return file_exists("{$directory}/onion.json");
    }

    public function saveManifest(string $location, Manifest $manifest): bool
    {
        return 1 > file_put_contents(
            "{$location}/onion.json",
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
