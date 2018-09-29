<?php declare(strict_types=1);
namespace Onion\Cli\Manifest\Entities;

use Onion\Cli\Manifest\Entity;

class Command implements Entity
{
    private $name;
    private $handler;
    private $summary;
    private $description = '';

    private $flags = [];
    private $parameters = [];

    public function __construct(string $definition, string $handler, string $summary, array $parameters = [])
    {
        $this->name = $definition;
        $this->handler = $handler;
        $this->summary = $summary;

        foreach ($parameters as $parameter) {
            $this->parameters[$parameter['name']] = new Parameter(
                $parameter['name'],
                $parameter['description'] ?? '',
                $parameter['type'] ?? 'mixed',
                $parameter['required'] ?? false,
                $parameter['default'] ?? null
            );
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHandler(): string
    {
        return $this->handler;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function withParameter($parameter)
    {
        $self = clone $this;
        $self->parameters[$parameter->getName()] = $parameter;

        return $self;
    }

    public function withDescription(string $description)
    {
        $self = clone $this;
        $self->description = $description;

        return $self;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getFlags(): array
    {
        return $this->flags;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function jsonSerialize()
    {
        $command = [
            'definition' => $this->getName(),
            'handler' => $this->getHandler(),
            'summary' => $this->getSummary(),
        ];

        if ($this->getParameters() !== []) {
            $command['parameters'] = $this->getParameters();
        }

        if ($this->getDescription() !== '') {
            $command['description'] = $this->getDescription();
        }

        return $command;
    }
}
