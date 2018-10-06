<?php declare(strict_types=1);
namespace Onion\Cli\SemVer;

use Onion\Cli\SemVer\Version;

class Version
{
    protected $constraint;

    protected $major;
    protected $minor;
    protected $fix;

    protected $pre;
    protected $build;

    public function __construct(string $constraint)
    {
        if (!preg_match('~^(?P<constraint>\~|\^)?(?P<major>\d+).(?P<minor>\d+)(?:.(?P<fix>\d+))?(?:\-(?P<pre>[0-9A-Za-z-.]+))?(?:\+(?P<build>[0-9A-Za-z-]+))?$~i', $constraint, $matches)) {
            throw new \InvalidArgumentException(
                "Provided constraint '$version' does not appear to be a valid semver string"
            );
        }


        foreach ($matches as $name => $value) {
            $this->$name = $value;
        }
    }

    public function getMajor(): int
    {
        return (int) $this->major;
    }

    public function getMinor(): int
    {
        return (int) $this->minor;
    }

    public function getFix(): int
    {
        return (int) $this->fix;
    }

    public function getPreRelease(): ?string
    {
        return $this->pre;
    }

    public function getBuild(): ?string
    {
        return $this->build;
    }

    public function getConstraint(): ?string
    {
        return $this->constraint;
    }

    public function hasConstraint(): bool
    {
        return $this->constraint !== null;
    }

    public function isPreRelease(): bool
    {
        return $this->pre !== null;
    }

    public function hasBuild(): bool
    {
        return $this->build !== null;
    }

    public function compare(Version $version): int
    {
        $compare = $version->getMajor() <=> $this->getMajor();

        if ($compare === 0) {
            $compare = $version->getMinor() <=> $this->getMinor();
            if ($compare === 0) {
                $compare = $version->getFix() <=> $this->getFix();
                if ($compare === 0 && ($this->isPreRelease() || $version->isPreRelease())) {
                    if (!$this->isPreRelease() && $version->isPreRelease()) {
                        $compare = -1;
                    } elseif ($this->isPreRelease() && !$version->isPreRelease()) {
                        $compare = 1;
                    } else {
                        $compare = $version->getPreRelease() <=> $this->getPreRelease();

                        if ($compare === 0) {
                            $compare = $version->getBuild() <=> $this->getBuild();
                        }
                    }
                }
            }
        }

        return $compare;
    }

    public function satisfies(string $constraint)
    {
        $version = new Version($constraint);

        return $this->compare($version) !== -1;
    }

    public function __toString(): string
    {
        return
            ($this->hasConstraint() ? $this->getConstraint() : '') .
            "{$this->getMajor()}." .
            "{$this->getMinor()}." .
            "{$this->getFix()}" .
            ($this->isPreRelease() ? "-{$this->getPreRelease()}" : '') .
            ($this->hasBuild() ? "+{$this->getBuild()}" : '');
    }
}
