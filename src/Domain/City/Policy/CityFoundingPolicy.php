<?php

namespace App\Domain\City\Policy;

use App\Domain\City\Exception\InvalidTerrainException;
use App\Domain\City\Exception\PositionOccupiedException;
use App\Domain\City\ValueObject\Position;
use App\Domain\Map\ValueObject\TerrainType;

final readonly class CityFoundingPolicy
{
    private const array ALLOWED_TERRAINS = [
        TerrainType::PLAINS,
        TerrainType::FOREST,
        TerrainType::DESERT
    ];

    public function canFoundCity(
        Position    $position,
        TerrainType $terrain,
        array       $existingCityPositions = []
    ): bool
    {
        return $this->isTerrainAllowed($terrain)
            && !$this->isPositionOccupied($position, $existingCityPositions);
    }

    public function validateCityFounding(
        Position    $position,
        TerrainType $terrain,
        array       $existingCityPositions = []
    ): void
    {
        if (!$this->isTerrainAllowed($terrain)) {
            throw InvalidTerrainException::create($position, $terrain);
        }

        if ($this->isPositionOccupied($position, $existingCityPositions)) {
            throw PositionOccupiedException::create($position);
        }
    }

    private function isTerrainAllowed(TerrainType $terrain): bool
    {
        return in_array($terrain, self::ALLOWED_TERRAINS, true);
    }

    private function isPositionOccupied(Position $position, array $existingPositions): bool
    {
        return array_any(
            $existingPositions,
            fn(Position $existingPosition) => $position->isEqual($existingPosition)
        );
    }
}
