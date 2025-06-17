<?php

namespace App\Domain\Technology\Effect;
final readonly class BonusEffect implements TechnologyEffectInterface
{
    public function __construct(
        private string $bonusType,
        private int    $bonusValue,
        private string $target
    )
    {
    }

    public function apply(mixed $context): void
    {
    }

    public function getName(): string
    {
        return "{$this->bonusType} Bonus";
    }

    public function getDescription(): string
    {
        return "Provides +{$this->bonusValue} {$this->bonusType} to {$this->target}";
    }

    public function getBonusType(): string
    {
        return $this->bonusType;
    }

    public function getBonusValue(): int
    {
        return $this->bonusValue;
    }

    public function getTarget(): string
    {
        return $this->target;
    }
}
