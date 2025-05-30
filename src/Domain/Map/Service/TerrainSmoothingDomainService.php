<?php

namespace App\Domain\Map\Service;

use App\Domain\Map\Enum\TerrainType;

/**
 * TerrainSmoothingDomainService handles terrain compatibility domain logic
 *
 * Pure domain service that encapsulates business rules for terrain
 * compatibility, smoothing algorithms, and natural terrain transitions.
 * Contains domain knowledge about which terrains naturally appear together.
 */
class TerrainSmoothingDomainService
{
    /** @var array Terrain compatibility matrix - which terrains work well together */
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

    /**
     * Gets compatibility score between two terrain types
     *
     * @param TerrainType $terrainType1 First terrain type
     * @param TerrainType $terrainType2 Second terrain type
     * @return float Compatibility score (0.0 to 1.0)
     */
    public function getCompatibilityScore(TerrainType $terrainType1, TerrainType $terrainType2): float
    {
        return self::TERRAIN_COMPATIBILITY[$terrainType1->value][$terrainType2->value] ?? 0.0;
    }

    /**
     * Checks if two terrain types are compatible based on domain rules
     *
     * @param TerrainType $terrainType1 First terrain type
     * @param TerrainType $terrainType2 Second terrain type
     * @param float $threshold Minimum compatibility threshold (default: 0.5)
     * @return bool True if terrains are compatible
     */
    public function areTerrainTypesCompatible(TerrainType $terrainType1, TerrainType $terrainType2, float $threshold = 0.5): bool
    {
        return $this->getCompatibilityScore($terrainType1, $terrainType2) >= $threshold;
    }

    /**
     * Gets list of compatible terrain types for given terrain
     *
     * @param TerrainType $terrainType Terrain type to check
     * @param float $threshold Minimum compatibility threshold
     * @return array Array of compatible terrain types
     */
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

    /**
     * Determines if a tile should be replaced based on compatibility
     *
     * @param TerrainType $currentTerrain Current terrain type
     * @param array $neighborTerrains Array of neighbor terrain types
     * @param float $threshold Compatibility threshold for replacement
     * @return bool True if tile should be replaced
     */
    public function shouldReplaceForCompatibility(TerrainType $currentTerrain, array $neighborTerrains, float $threshold = 0.3): bool
    {
        if (empty($neighborTerrains)) {
            return false;
        }

        // Check if any neighbors are incompatible using PHP 8.4 array_any
        $hasIncompatibleNeighbors = array_any(
            $neighborTerrains,
            fn($neighborTerrain) => !$this->areTerrainTypesCompatible(
                $currentTerrain,
                TerrainType::from($neighborTerrain),
                $threshold
            )
        );

        // If no incompatible neighbors, no need to replace
        if (!$hasIncompatibleNeighbors) {
            return false;
        }

        // Count incompatible neighbors for more detailed analysis
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

    /**
     * Finds best replacement terrain based on neighbor compatibility
     *
     * @param array $neighborTerrains Array of neighbor terrain types with counts
     * @return TerrainType|null Best terrain type or null if none suitable
     */
    public function findBestReplacementTerrain(array $neighborTerrains): ?TerrainType
    {
        if (empty($neighborTerrains)) {
            return null;
        }

        // If only one terrain type, return it directly
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
                return $averageCompatibility * $count; // Weight by frequency
            },
            array_keys($neighborTerrains),
            $neighborTerrains
        );

        $bestTerrainKey = array_search(max($terrainScores), $terrainScores);
        return $bestTerrainKey !== false
            ? TerrainType::from(array_keys($neighborTerrains)[$bestTerrainKey])
            : null;
    }

    /**
     * Gets the full compatibility matrix for external analysis
     *
     * @return array Complete terrain compatibility matrix
     */
    public function getCompatibilityMatrix(): array
    {
        return self::TERRAIN_COMPATIBILITY;
    }

    /**
     * Validates compatibility matrix configuration
     *
     * @param array $matrix Compatibility matrix to validate
     * @return bool True if matrix is valid
     */
    public function isValidCompatibilityMatrix(array $matrix): bool
    {
        $terrainTypes = array_map(fn($terrain) => $terrain->value, TerrainType::cases());

        // Check if all terrain types exist as keys
        foreach ($terrainTypes as $terrain1) {
            if (!isset($matrix[$terrain1])) {
                return false;
            }

            // Check if each terrain has compatibility with all other terrains
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
