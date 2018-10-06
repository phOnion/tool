<?php declare(strict_types=1);
namespace Onion\Cli\SemVer;

class MutableVersion extends Version
{
    public function setMajor(int $major): void
    {
        $this->major = $major;
    }

    public function setMinor(int $minor): void
    {
        $this->minor = $minor;
    }

    public function setFix(int $fix): void
    {
        $this->fix = $fix;
    }

    public function setPreRelease(?string $preRelease): void
    {
        $this->pre = $preRelease;
    }

    public function setBuild(?string $build): void
    {
        $this->build = $build;
    }

    public function setConstraint(?string $constraint): void
    {
        $this->constraint = $constraint;
    }
}
