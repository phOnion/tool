<?php declare(strict_types=1);
namespace Onion\Cli\Manifest\Entities;

use Onion\Cli\Manifest\Entity;

class Index implements Entity
{
    private $type;
    private $file;

    public function __construct(string $type, string $file)
    {
        $this->type = $type;
        $this->file = $file;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function jsonSerialize()
    {
        return [
            'type' => $this->type,
            'file' => $this->file,
        ];
    }
}
