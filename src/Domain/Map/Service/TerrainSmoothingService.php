<?php

namespace App\Domain\Map\Service;

use App\Domain\Map\ValueObject\TerrainType;

class TerrainSmoothingService
{
    private const array TERRAIN_COMPATIBILITY = [
        TerrainType::PLAINS->value => [
            TerrainType::PLAINS->value => 1.0,
            TerrainType::FOREST->value => 0.8,
            TerrainType::WATER->value => 0.7,
            TerrainType::DESERT->value => 0.5,
            TerrainType::MOUNTAIN->value => 0.6,
            TerrainType::SWAMP->value => 0.4
        ],
        TerrainType::FOREST->value => [
            TerrainType::PLAINS->value => 0.8,
            TerrainType::FOREST->value => 1.0,
            TerrainType::WATER->value => 0.6,
            TerrainType::DESERT->value => 0.2,
            TerrainType::MOUNTAIN->value => 0.7,
            TerrainType::SWAMP->value => 0.6
        ],
        TerrainType::WATER->value => [
            TerrainType::PLAINS->value => 0.7,
            TerrainType::FOREST->value => 0.6,
            TerrainType::WATER->value => 1.0,
            TerrainType::DESERT->value => 0.1,
            TerrainType::MOUNTAIN->value => 0.3,
            TerrainType::SWAMP->value => 0.8
        ],
        TerrainType::DESERT->value => [
            TerrainType::PLAINS->value => 0.5,
            TerrainType::FOREST->value => 0.2,
            TerrainType::WATER->value => 0.1,
            TerrainType::DESERT->value => 1.0,
            TerrainType::MOUNTAIN->value => 0.8,
            TerrainType::SWAMP->value => 0.1
        ],
        TerrainType::MOUNTAIN->value => [
            TerrainType::PLAINS->value => 0.6,
            TerrainType::FOREST->value => 0.7,
            TerrainType::WATER->value => 0.3,
            TerrainType::DESERT->value => 0.8,
            TerrainType::MOUNTAIN->value => 1.0,
            TerrainType::SWAMP->value => 0.2
        ],
        TerrainType::SWAMP->value => [
            TerrainType::PLAINS->value => 0.4,
            TerrainType::FOREST->value => 0.6,
            TerrainType::WATER->value => 0.8,
            TerrainType::DESERT->value => 0.1,
            TerrainType::MOUNTAIN->value => 0.2,
            TerrainType::SWAMP->value => 1.0
        ]
    ];

    public function getCompatibilityScore(TerrainType $terrainType1, TerrainType $terrainType2): float
    {
        return self::TERRAIN_COMPATIBILITY[$terrainType1->value][$terrainType2->value] ?? 0.0;
    }

    public function areTerrainTypesCompatible(TerrainType $terrainType1, TerrainType $terrainType2, float $threshold = 0.5): bool
    {
        return $this->getCompatibilityScore($terrainType1, $terrainType2) >= $threshold;
    }

    public function getCompatibleTerrainTypes(TerrainType $terrainType, float $threshold = 0.5): array
    {
        $compatibilities = self::TERRAIN_COMPATIBILITY[$terrainType->value] ?? [];

        return array_map(
            fn($terrain) => TerrainType::from($terrain),
            array_keys(
                array_filter($compatibilities, fn($score) => $score >= $threshold)
            )
        );
    }

    public function shouldReplaceForCompatibility(TerrainType $currentTerrain, array $neighborTerrains, float $threshold = 0.3): bool
    {
        if (empty($neighborTerrains)) {
            return false;
        }

        $hasIncompatibleNeighbors = array_any(
            $neighborTerrains,
            fn($neighborTerrain) => !$this->areTerrainTypesCompatible(
                $currentTerrain,
                TerrainType::from($neighborTerrain),
                $threshold
            )
        );

        if (!$hasIncompatibleNeighbors) {
            return false;
        }

        $incompatibleCount = count(array_filter(
            $neighborTerrains,
            fn($neighborTerrain) => !$this->areTerrainTypesCompatible(
                $currentTerrain,
                TerrainType::from($neighborTerrain),
                $threshold
            )
        ));

        return ($incompatibleCount / count($neighborTerrains)) > 0.5;
    }

    public function findBestReplacementTerrain(array $neighborTerrains): ?TerrainType
    {
        if (empty($neighborTerrains)) {
            return null;
        }

        if (count($neighborTerrains) === 1) {
            $terrain = array_key_first($neighborTerrains);
            return TerrainType::from($terrain);
        }

        $terrainScores = array_map(
            function ($terrain, $count) use ($neighborTerrains) {
                $terrainType = TerrainType::from($terrain);

                $compatibilityScores = array_map(
                    fn($otherTerrain) => $terrain !== $otherTerrain
                        ? $this->getCompatibilityScore($terrainType, TerrainType::from($otherTerrain))
                        : 0.0,
                    array_keys($neighborTerrains)
                );

                $averageCompatibility = array_sum($compatibilityScores) / max(1, count($compatibilityScores) - 1);
                return $averageCompatibility * $count;
            },
            array_keys($neighborTerrains),
            $neighborTerrains
        );

        $bestTerrainKey = array_search(max($terrainScores), $terrainScores);
        return $bestTerrainKey !== false
            ? TerrainType::from(array_keys($neighborTerrains)[$bestTerrainKey])
            : null;
    }

    public function getCompatibilityMatrix(): array
    {
        return self::TERRAIN_COMPATIBILITY;
    }

    public function isValidCompatibilityMatrix(array $matrix): bool
    {
        $terrainTypes = array_map(fn($terrain) => $terrain->value, TerrainType::cases());

        foreach ($terrainTypes as $terrain1) {
            if (!isset($matrix[$terrain1])) {
                return false;
            }

            foreach ($terrainTypes as $terrain2) {
                if (!isset($matrix[$terrain1][$terrain2])) {
                    return false;
                }

                $score = $matrix[$terrain1][$terrain2];
                if (!is_float($score) || $score < 0.0 || $score > 1.0) {
                    return false;
                }
            }
        }

        return true;
    }
}
