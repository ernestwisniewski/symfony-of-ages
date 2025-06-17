<?php

namespace App\Domain\Technology\Effect;

use App\Domain\Unit\ValueObject\UnitType;

final readonly class UnlockUnitEffect implements TechnologyEffectInterface
{
    public function __construct(
        private UnitType $unitType
    )
    {
    }

    public function apply(mixed $context): void
    {
    }

    public function getName(): string
    {
        return "Unlock {$this->unitType->value}";
    }

    public function getDescription(): string
    {
        return "Unlocks the ability to create {$this->unitType->value} units";
    }

    public function getUnitType(): UnitType
    {
        return $this->unitType;
    }
}
