<?php

namespace App\Domain\Map\Service;

use App\Domain\Map\ValueObject\TerrainType;

class TerrainGenerationService
{
    private const array TERRAIN_WEIGHTS = [
        TerrainType::PLAINS->value => 35,
        TerrainType::FOREST->value => 25,
        TerrainType::MOUNTAIN->value => 15,
        TerrainType::WATER->value => 10,
        TerrainType::DESERT->value => 10,
        TerrainType::SWAMP->value => 5
    ];
    public array $terrainWeights {
        get => self::TERRAIN_WEIGHTS;
    }
    public int $totalWeight {
        get => array_sum(self::TERRAIN_WEIGHTS);
    }
    public array $idealDistribution {
        get => array_map(
            fn($weight) => round($weight, 2),
            self::TERRAIN_WEIGHTS
        );
    }

    public function getWeightedRandomTerrain(): TerrainType
    {
        $totalWeight = $this->totalWeight;
        $random = mt_rand(1, $totalWeight);
        $currentWeight = 0;
        foreach (self::TERRAIN_WEIGHTS as $terrainValue => $weight) {
            $currentWeight += $weight;
            if ($currentWeight >= $random) {
                return TerrainType::from($terrainValue);
            }
        }
        return TerrainType::PLAINS;
    }

    public function createTerrainTile(TerrainType $terrainType, int $row, int $col): array
    {
        $properties = $terrainType->getProperties();
        return [
            'type' => $terrainType->value,
            'name' => $properties['name'],
            'coordinates' => ['row' => $row, 'col' => $col],
            'properties' => $properties
        ];
    }

    public function getTerrainWeights(): array
    {
        return $this->terrainWeights;
    }

    public function areValidTerrainWeights(array $weights): bool
    {
        if (empty($weights)) {
            return false;
        }
        $terrainValues = array_map(fn($terrain) => $terrain->value, TerrainType::cases());
        $hasValidTerrainTypes = array_all(
            array_keys($weights),
            fn($terrain) => in_array($terrain, $terrainValues)
        );
        if (!$hasValidTerrainTypes) {
            return false;
        }
        return array_all(
            $weights,
            fn($weight) => is_int($weight) && $weight >= 0
        );
    }

    public function getIdealTerrainDistribution(): array
    {
        return $this->idealDistribution;
    }
}
