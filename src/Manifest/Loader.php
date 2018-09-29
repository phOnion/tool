<?php declare(strict_types=1);
namespace Onion\Cli\Manifest;

use function Sabre\Xml\Deserializer\keyValue;
use function Sabre\Xml\Deserializer\valueObject;
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

    /**
     * @var string
     */
    private $manifestFileName;

    public function __construct(array $manifestMap)
    {
        $this->entityMaps = $manifestMap;
        $composer = json_decode(file_get_contents(getcwd() . '/composer.json'), true);

        $this->manifestFileName = $composer['extra']['onion']['manifest']['filename'] ?? 'onion.json';
    }

    private function loadManifest(): array
    {
        if (!file_exists(getcwd() . "/{$this->manifestFileName}")) {
            throw new \RuntimeException("Manifest file '{$this->manifestFileName}' not found in current director. Did you forget to init?");
        }

        return json_decode(file_get_contents(getcwd() . "/{$this->manifestFileName}"), true);
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

    public function getManifest(): Manifest
    {
        $raw = $this->loadManifest();

        $manifest = new Manifest(
            $raw['name'] ?? '',
            $raw['version'] ?? '0.0.0',
            $this->getSection($raw, 'links')
        );

        $manifest = $manifest->withCommands($this->getSection($raw, 'commands'));

        return $manifest;
    }

    public function saveManifest(string $location, Manifest $manifest): bool
    {
        return 1 > file_put_contents(
            $location . DIRECTORY_SEPARATOR . $this->manifestFileName,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
