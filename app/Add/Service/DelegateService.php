<?php declare(strict_types=1);
namespace App\Add\Service;

use App\Add\StrategyInterface;


class DelegateService
{
    private $strategies = [];

    public function addStrategy(StrategyInterface $strategy): self
    {
        $self = clone $this;
        $self->strategies[$strategy->getType()] = $strategy;

        return $self;
    }

    public function getStrategy(string $type): StrategyInterface
    {
        if (!isset($this->strategies[$type])) {
            throw new \UnexpectedValueException(
                "Specified strategy '$type' does not exist"
            );
        }

        return $this->strategies[$type];
    }
}
