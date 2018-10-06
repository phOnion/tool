<?php declare(strict_types=1);
namespace Onion\Cli\Manifest\Entities;

use Onion\Cli\Manifest\Entity;

class Index implements Entity
{
    private $indexes;

    public function __cosntruct(array $indexes)
    {
        $this->indexes = $indexes;
    }

    public function getIndex(string $type, $default = null)
    {
        return $this->indexes[$type] ?? $default;
    }
}
