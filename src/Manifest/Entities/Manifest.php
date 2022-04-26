<?php

declare(strict_types=1);

namespace Onion\Cli\Manifest\Entities;

use Onion\Cli\Manifest\Entities\Link;
use Onion\Cli\Manifest\Entity;

class Manifest implements Entity
{
    /** @var string $name */
    private $name;
    /** @var string $version */
    private $version;
    /** @var string $license */
    private $license = 'MIT';

    /** @var Link[] */
    protected $links = [];
    /** @var Command[] */
    protected $commands = [];

    /** @var Repository[] */
    protected $repos = [];

    /** @var Dependency[] */
    protected $dependencies = [];

    public function __construct(
        string $name,
        string $version,
        string $license = 'none',
        iterable $links = []
    ) {

        $this->name = $name;
        $this->version = $version;
        $this->license = $license;

        $this->links = $links;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Manifest
    {
        $self = clone $this;
        $self->name = $name;

        return $self;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): Manifest
    {
        $self = clone $this;
        $self->version = $version;

        return $self;
    }

    public function getLicense(): string
    {
        return $this->license;
    }

    public function setLicense(string $license)
    {
        $self = clone $this;
        $self->license = $license;

        return $self;
    }

    public function getLinks(): iterable
    {
        return $this->links;
    }

    public function withLinks(iterable $links)
    {
        $self = clone $this;
        $self->links = [];
        foreach ($links as $link) {
            $self = $self->addLink($link);
        }

        return $self;
    }

    public function addLink(Link $link): Manifest
    {
        $self = clone $this;
        $self->links[] = $link;

        return $self;
    }

    public function withCommands(array $commands): Manifest
    {
        $self = clone $this;
        $self->commands = $commands;

        return $self;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }

    public function withRepositories(array $repos): Manifest
    {
        $self = clone $this;
        $self->repos = $repos;

        return $self;
    }

    public function getRepositories(): array
    {
        return $this->repos;
    }

    public function getRepositoryByName(string $name): ?Repository
    {
        foreach ($this->repos as $repo) {
            if ($repo->getName() === $name) {
                return $repo;
            }
        }
    }


    public function withDependencies(array $dependencies): Manifest
    {
        $self = clone $this;
        $self->dependencies = $dependencies;

        return $self;
    }

    public function withAddedDependency(Dependency $dependency): Manifest
    {
        $self = clone $this;
        $self->dependencies[] = $dependency;

        return $self;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function jsonSerialize(): array
    {
        $result = [
            'name' => $this->getName(),
            'version' => $this->getVersion(),
            'license' => $this->getLicense()
        ];

        if ($this->getCommands() !== []) {
            $result['commands'] = $this->getCommands();
        }

        if ($this->getLinks() !== []) {
            $result['links'] = $this->getLinks();
        }

        if ($this->getDependencies() !== []) {
            $result['dependencies'] = $this->getDependencies();
        }

        return $result;
    }
}
