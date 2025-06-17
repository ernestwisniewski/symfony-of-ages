<?php

namespace App\Domain\Unit\Policy;

use App\Domain\Shared\ValueObject\Position;
use App\Domain\Unit\Exception\InvalidMovementException;
use App\Domain\Unit\ValueObject\UnitType;

final readonly class UnitMovementPolicy
{
    public function canMove(
        Position $from,
        Position $to,
        UnitType $unitType,
        array    $existingUnits
    ): bool
    {
        return $this->isWithinRange($from, $to, $unitType->getMovementRange())
            && !$this->isPositionOccupied($to, $existingUnits);
    }

    public function validateMovement(
        Position $from,
        Position $to,
        UnitType $unitType,
        array    $existingUnits
    ): void
    {
        $maxRange = $unitType->getMovementRange();
        if (!$this->isWithinRange($from, $to, $maxRange)) {
            throw InvalidMovementException::tooFar($from, $to, $maxRange);
        }
        if ($this->isPositionOccupied($to, $existingUnits)) {
            throw InvalidMovementException::positionOccupied($to);
        }
    }

    private function isWithinRange(Position $from, Position $to, int $maxRange): bool
    {
        $distance = abs($to->x - $from->x) + abs($to->y - $from->y);
        return $distance <= $maxRange;
    }

    private function isPositionOccupied(Position $position, array $existingUnits): bool
    {
        return array_any(
            $existingUnits,
            fn($unit) => $unit['x'] === $position->x && $unit['y'] === $position->y
        );
    }
}
