<?php declare(strict_types=1);
namespace Onion\Cli\Manifest\Entities;

use Onion\Cli\Manifest\Entity;

class Repository implements Entity
{
    private $name;
    private $vendor;
    private $project;
    private $branch;

    public function __construct(string $name, string $vendor, string $project, string $branch = 'master')
    {
        $this->name = $name;
        $this->vendor = $vendor;
        $this->project = $project;
        $this->branch = $branch;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVendor(): string
    {
        return $this->vendor;
    }

    public function getProject(): string
    {
        return $this->project;
    }

    public function getBranch(): string
    {
        return $this->branch;
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'vendor' => $this->getVendor(),
            'project' => $this->getProject(),
            'branch' => $this->getBranch(),
        ];
    }
}
