<?php declare(strict_types=1);
namespace Onion\Cli\SemVer;

use Composer\Semver\Comparator;

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
        return $this->pre !== null && $this->pre !== '';
    }

    public function hasBuild(): bool
    {
        return $this->build !== null && $this->build !== '';
    }

    public function getBaseVersion(): string
    {
        return "{$this->getMajor()}." .
            "{$this->getMinor()}." .
            "{$this->getFix()}" .
            ($this->isPreRelease() ? "-{$this->getPreRelease()}" : '') .
            ($this->hasBuild() ? "+{$this->getBuild()}" : '');
    }

    public function compare(Version $version): int
    {
        $version1 = $this->getBaseVersion();
        $version2 = $version->getBaseVersion();
        if (Comparator::greaterThan($version1, $version2)) {
            return 1;
        }

        if (Comparator::lessThan($version1, $version2)) {
            return -1;
        }

        return 0;
    }

    public function satisfies(string $constraint)
    {
        return \Composer\Semver\Semver::satisfies($this->getBaseVersion(), $constraint);
    }

    public function __toString(): string
    {
        return ($this->hasConstraint() ? $this->getConstraint() : '') .
            $this->getBaseVersion();
    }
}
