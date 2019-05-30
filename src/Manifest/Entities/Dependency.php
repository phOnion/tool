<?php declare(strict_types=1);
namespace Onion\Cli\Manifest\Entities;

use Onion\Cli\Manifest\Entity;

class Dependency implements Entity
{
    private $name;
    private $version;
    private $repository;

    public function __construct(string $name, string $version, ?string $repository = null)
    {
        $this->name = $name;
        $this->version = $version;
        $this->repository = $repository;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getRepository(): ?string
    {
        return $this->repository;
    }

    public function jsonSerialize()
    {
        return array_filter([
            'name' => $this->getName(),
            'version' => $this->getVersion(),
            'repository' => $this->getRepository(),
        ]);
    }
}
