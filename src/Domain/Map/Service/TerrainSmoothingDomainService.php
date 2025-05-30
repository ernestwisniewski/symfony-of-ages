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
        $compatible = [];
        $compatibilities = self::TERRAIN_COMPATIBILITY[$terrainType->value] ?? [];

        foreach ($compatibilities as $terrain => $score) {
            if ($score >= $threshold) {
                $compatible[] = TerrainType::from($terrain);
            }
        }

        return $compatible;
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

        $incompatibleCount = 0;
        $totalNeighbors = count($neighborTerrains);

        foreach ($neighborTerrains as $neighborTerrain) {
            if (!$this->areTerrainTypesCompatible($currentTerrain, TerrainType::from($neighborTerrain), $threshold)) {
                $incompatibleCount++;
            }
        }

        // Replace if more than half of neighbors are incompatible
        return ($incompatibleCount / $totalNeighbors) > 0.5;
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

        $bestTerrain = null;
        $bestScore = 0.0;

        foreach ($neighborTerrains as $terrain => $count) {
            $terrainType = TerrainType::from($terrain);
            $totalCompatibility = 0.0;
            $comparisons = 0;

            // Calculate average compatibility with all other neighbors
            foreach ($neighborTerrains as $otherTerrain => $otherCount) {
                if ($terrain !== $otherTerrain) {
                    $totalCompatibility += $this->getCompatibilityScore($terrainType, TerrainType::from($otherTerrain));
                    $comparisons++;
                }
            }

            $averageCompatibility = $comparisons > 0 ? $totalCompatibility / $comparisons : 0.0;
            $weightedScore = $averageCompatibility * $count; // Weight by frequency

            if ($weightedScore > $bestScore) {
                $bestScore = $weightedScore;
                $bestTerrain = $terrainType;
            }
        }

        return $bestTerrain;
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
        $terrainTypes = array_map(fn ($terrain) => $terrain->value, TerrainType::cases());

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
