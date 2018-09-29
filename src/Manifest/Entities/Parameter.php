<?php declare(strict_types=1);
namespace Onion\Cli\Manifest\Entities;

use Onion\Cli\Manifest\Entity;

class Parameter implements Entity
{
    private $name;
    private $description;
    private $type;
    private $required;
    private $default;

    public function __construct(
        string $name,
        string $description,
        string $type,
        bool $required = false,
        ?string $default = null
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->required = $required;
        $this->default = $default;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function hasDefault(): bool
    {
        return $this->default !== null;
    }

    public function getDefault(): string
    {
        return $this->default;
    }

    public function jsonSerialize()
    {
        $parameter = [
            'name' => $this->getName(),
            'type' => $this->getType(),
            'required' => $this->isRequired(),
            'description' => $this->getDescription(),
        ];

        if ($this->hasDefault()) {
            $parameter['default'] = $this->getDefault();
        }

        return $parameter;
    }
}
