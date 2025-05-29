<?php

namespace App\Application\Map\Service;

use App\Domain\Player\Enum\TerrainType;

/**
 * TerrainClusteringService handles terrain clustering algorithms
 *
 * Responsible for applying clustering algorithms to create realistic terrain
 * formations by encouraging similar terrain types to group together.
 * Follows Single Responsibility Principle by focusing only on clustering logic.
 */
class TerrainClusteringService
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

    public function __construct(
        private readonly HexNeighborService           $neighborService,
        private readonly BaseTerrainGenerationService $terrainGenerationService
    )
    {
    }

    /**
     * Applies clustering algorithm to create realistic terrain formations
     *
     * @param array $map Current map state
     * @param int $rows Number of rows
     * @param int $cols Number of columns
     * @param int $iterations Number of clustering passes
     *
     * @return array Map with clustering applied
     */
    public function applyClustering(array $map, int $rows, int $cols, int $iterations = 2): array
    {
        for ($iteration = 0; $iteration < $iterations; $iteration++) {
            $newMap = $map;

            for ($row = 0; $row < $rows; $row++) {
                for ($col = 0; $col < $cols; $col++) {
                    $currentTerrain = $map[$row][$col]['type'];
                    $neighbors = $this->neighborService->getNeighbors($map, $row, $col, $rows, $cols);

                    // Check if we should cluster this terrain type
                    if (isset(self::TERRAIN_CLUSTERS[$currentTerrain])) {
                        $clusterChance = self::TERRAIN_CLUSTERS[$currentTerrain];
                        $sameTerrainCount = $this->countSameTerrainNeighbors($neighbors, $currentTerrain);
                        $totalNeighbors = count($neighbors);

                        // If this terrain should cluster and we have few same neighbors
                        if ($totalNeighbors > 0 && $sameTerrainCount < 2) {
                            $shouldCluster = mt_rand(1, 100) <= ($clusterChance * 100);

                            if ($shouldCluster && $totalNeighbors > 0) {
                                $newMap = $this->spreadTerrainToNeighbor($newMap, $neighbors, $currentTerrain);
                            }
                        }
                    }
                }
            }

            $map = $newMap;
        }

        return $map;
    }

    /**
     * Counts how many neighbors have the same terrain type
     *
     * @param array $neighbors Array of neighbor tiles
     * @param string $terrainType Terrain type to count
     * @return int Number of neighbors with same terrain type
     */
    private function countSameTerrainNeighbors(array $neighbors, string $terrainType): int
    {
        $count = 0;
        foreach ($neighbors as $neighbor) {
            if ($neighbor['type'] === $terrainType) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Attempts to spread current terrain type to a neighboring tile
     *
     * @param array $map Current map state
     * @param array $neighbors Array of neighbor tiles
     * @param string $currentTerrain Current terrain type to spread
     * @return array Updated map with potential terrain spread
     */
    private function spreadTerrainToNeighbor(array $map, array $neighbors, string $currentTerrain): array
    {
        // Find a neighbor of a different type to convert
        foreach ($neighbors as $neighbor) {
            if ($neighbor['type'] === $currentTerrain) {
                continue; // Already same type
            }

            // Random chance to convert neighbor (30% chance)
            if (mt_rand(1, 100) <= 30) {
                $terrainType = TerrainType::from($currentTerrain);
                $neighborRow = $neighbor['coordinates']['row'];
                $neighborCol = $neighbor['coordinates']['col'];

                $map[$neighborRow][$neighborCol] = $this->terrainGenerationService->createTerrainTile(
                    $terrainType,
                    $neighborRow,
                    $neighborCol
                );
                break; // Only convert one neighbor per iteration
            }
        }

        return $map;
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
     * Checks if a terrain type should cluster
     *
     * @param string $terrainType Terrain type to check
     * @return bool True if terrain type has clustering behavior
     */
    public function shouldTerrainCluster(string $terrainType): bool
    {
        return isset(self::TERRAIN_CLUSTERS[$terrainType]);
    }

    /**
     * Gets clustering probability for a specific terrain type
     *
     * @param string $terrainType Terrain type to check
     * @return float Clustering probability (0.0 to 1.0)
     */
    public function getClusteringProbability(string $terrainType): float
    {
        return self::TERRAIN_CLUSTERS[$terrainType] ?? 0.0;
    }
}
