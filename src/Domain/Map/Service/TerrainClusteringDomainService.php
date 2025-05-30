<?php

namespace App\Domain\Map\Service;

use App\Domain\Player\Enum\TerrainType;

/**
 * TerrainClusteringDomainService handles terrain clustering domain logic
 *
 * Pure domain service that encapsulates business rules for terrain
 * clustering, including clustering probabilities and clustering algorithms.
 * Contains domain knowledge about natural terrain formation.
 */
class TerrainClusteringDomainService
{
    /** @var array Clustering probabilities for terrain types to appear near themselves */
    private const array TERRAIN_CLUSTERS = [
        TerrainType::WATER->value => 0.7,    // High clustering - water bodies
        TerrainType::FOREST->value => 0.6,   // Good clustering - forest patches
        TerrainType::DESERT->value => 0.6,   // Good clustering - desert regions
        TerrainType::MOUNTAIN->value => 0.5, // Moderate clustering - mountain ranges
        TerrainType::SWAMP->value => 0.4,    // Low clustering - scattered swamps
        TerrainType::PLAINS->value => 0.3    // Minimal clustering - fills gaps
    ];

    /**
     * Determines if a terrain type should cluster
     *
     * @param TerrainType $terrainType Terrain type to check
     * @return bool True if terrain type has clustering behavior
     */
    public function shouldTerrainCluster(TerrainType $terrainType): bool
    {
        return isset(self::TERRAIN_CLUSTERS[$terrainType->value]);
    }

    /**
     * Gets clustering probability for a specific terrain type
     *
     * @param TerrainType $terrainType Terrain type to check
     * @return float Clustering probability (0.0 to 1.0)
     */
    public function getClusteringProbability(TerrainType $terrainType): float
    {
        return self::TERRAIN_CLUSTERS[$terrainType->value] ?? 0.0;
    }

    /**
     * Determines if terrain should spread to neighbor based on domain rules
     *
     * @param TerrainType $terrainType Current terrain type
     * @param int $sameNeighborCount Count of same terrain neighbors
     * @param int $totalNeighbors Total neighbor count
     * @return bool True if terrain should spread
     */
    public function shouldSpreadToNeighbor(TerrainType $terrainType, int $sameNeighborCount, int $totalNeighbors): bool
    {
        // Domain rule: Don't spread if already well-clustered
        if ($sameNeighborCount >= 2) {
            return false;
        }

        // Domain rule: Check clustering probability
        $clusterChance = $this->getClusteringProbability($terrainType);
        return mt_rand(1, 100) <= ($clusterChance * 100);
    }

    /**
     * Selects neighbor to convert based on domain rules
     *
     * @param array $neighbors Array of neighbor tiles
     * @param TerrainType $currentTerrain Current terrain type to spread
     * @return array|null Neighbor to convert or null
     */
    public function selectNeighborToConvert(array $neighbors, TerrainType $currentTerrain): ?array
    {
        // Find neighbors of different type
        $differentNeighbors = array_filter(
            $neighbors,
            fn ($neighbor) => $neighbor['type'] !== $currentTerrain->value
        );

        if (empty($differentNeighbors)) {
            return null;
        }

        // Domain rule: 30% chance to convert neighbor
        if (mt_rand(1, 100) <= 30) {
            return $differentNeighbors[array_rand($differentNeighbors)];
        }

        return null;
    }

    /**
     * Counts neighbors of same terrain type
     *
     * @param array $neighbors Array of neighbor tiles
     * @param TerrainType $terrainType Terrain type to count
     * @return int Number of neighbors with same terrain type
     */
    public function countSameTerrainNeighbors(array $neighbors, TerrainType $terrainType): int
    {
        $count = 0;
        foreach ($neighbors as $neighbor) {
            if ($neighbor['type'] === $terrainType->value) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Gets the clustering configuration for terrain types
     *
     * @return array Terrain clustering probabilities
     */
    public function getClusteringConfiguration(): array
    {
        return self::TERRAIN_CLUSTERS;
    }

    /**
     * Validates clustering configuration
     *
     * @param array $clusterConfig Clustering configuration to validate
     * @return bool True if configuration is valid
     */
    public function isValidClusteringConfiguration(array $clusterConfig): bool
    {
        foreach ($clusterConfig as $terrain => $probability) {
            // Check if terrain type exists
            if (!TerrainType::tryFrom($terrain)) {
                return false;
            }

            // Check if probability is valid (0.0 to 1.0)
            if (!is_float($probability) || $probability < 0.0 || $probability > 1.0) {
                return false;
            }
        }

        return true;
    }
}
