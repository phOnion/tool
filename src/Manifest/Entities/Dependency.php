<?php declare(strict_types=1);
namespace Onion\Cli\Manifest\Entities;

use Onion\Cli\Manifest\Entity;

class Dependency implements Entity
{
    private $name;
    private $version;
    private $alias;

    public function __construct(string $name, string $version, ?string $alias = null)
    {
        $this->name = $name;
        $this->version = $version;
        $this->alias = $alias;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function jsonSerialize()
    {
        return array_filter([
            'name' => $this->getName(),
            'version' => $this->getVersion(),
            'alias' => $this->getAlias(),
        ]);
    }
}
