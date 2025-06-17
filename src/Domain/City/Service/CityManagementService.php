<?php

namespace App\Domain\City\Service;

use App\Domain\City\Policy\CityFoundingPolicy;
use App\Domain\Map\ValueObject\TerrainType;
use App\Domain\Shared\ValueObject\Position;

final readonly class CityManagementService
{
    public function __construct(
        private CityFoundingPolicy $cityFoundingPolicy
    )
    {
    }

    public function validateCityFounding(
        Position    $position,
        TerrainType $terrain,
        array       $existingCityPositions = []
    ): void
    {
        $this->cityFoundingPolicy->validateCityFounding($position, $terrain, $existingCityPositions);
    }

    public function canFoundCity(
        Position    $position,
        TerrainType $terrain,
        array       $existingCityPositions = []
    ): bool
    {
        return $this->cityFoundingPolicy->canFoundCity($position, $terrain, $existingCityPositions);
    }

    public function findSuitablePositions(
        array $mapTiles,
        array $existingCityPositions = []
    ): array
    {
        $suitablePositions = [];
        foreach ($mapTiles as $tile) {
            $position = new Position($tile['x'], $tile['y']);
            $terrain = TerrainType::from($tile['terrain']);
            if ($this->canFoundCity($position, $terrain, $existingCityPositions)) {
                $suitablePositions[] = $position;
            }
        }
        return $suitablePositions;
    }
}
