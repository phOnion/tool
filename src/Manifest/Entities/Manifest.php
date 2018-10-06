<?php declare(strict_types=1);
namespace Onion\Cli\Manifest\Entities;

use Onion\Cli\Manifest\Entity;
use Onion\Cli\Manifest\Entities\Package;
use Onion\Cli\Manifest\Entities\Maintainer;
use Onion\Cli\Manifest\Entities\Link;
use Onion\Cli\Manifest\Entities\Manifest;
use Onion\Cli\SemVer\Version;

class Manifest implements Entity
{
    /** @var string */
    private $name;
    /** @var string */
    private $version;

    /** @var Link[] */
    protected $links = [];
    /** @var Command[] */
    protected $commands = [];
    /** @var Index[] */
    protected $index = [];

    public function __construct(
        string $name,
        string $version,
        iterable $links = [],
        iterable $commands = []
    ) {

        $this->name = $name;
        $this->version = $version;

        $this->links = $links;
        $this->commands = $commands;
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

    public function getLinks(): iterable
    {
        return $this->links;
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

    public function withIndex(array $index): Manifest
    {
        $self = clone $this;
        foreach ($index as $item) {
            $self->index[$item->getType()] = $item;
        }

        return $self;
    }

    public function getIndex(string $type = null)
    {
        return $type === null ? $this->index : $this->index[$type];
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'version' => $this->getVersion(),
            'links' => $this->getLinks(),
            'commands' => $this->getCommands(),
        ];
    }
}
