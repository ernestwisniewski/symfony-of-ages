<?php

namespace App\Domain\Unit\Policy;

use App\Domain\City\ValueObject\Position;
use App\Domain\Map\ValueObject\TerrainType;
use App\Domain\Unit\Exception\InvalidMovementException;

final readonly class UnitCreationPolicy
{
    public function canCreateUnit(
        Position    $position,
        TerrainType $terrain,
        array       $existingUnits
    ): bool
    {
        return $this->isTerrainPassable($terrain)
            && !$this->isPositionOccupied($position, $existingUnits);
    }

    public function validateUnitCreation(
        Position    $position,
        TerrainType $terrain,
        array       $existingUnits
    ): void
    {
        if (!$this->isTerrainPassable($terrain)) {
            throw InvalidMovementException::invalidTerrain($position);
        }

        if ($this->isPositionOccupied($position, $existingUnits)) {
            throw InvalidMovementException::positionOccupied($position);
        }
    }

    private function isTerrainPassable(TerrainType $terrain): bool
    {
        return $terrain->isPassable();
    }

    private function isPositionOccupied(Position $position, array $existingUnits): bool
    {
        return array_any(
            $existingUnits,
            fn($unit) => $unit['x'] === $position->x && $unit['y'] === $position->y
        );
    }
}
