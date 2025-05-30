<?php

namespace App\Application\Map\Service;

use App\Domain\Map\Service\TerrainClusteringDomainService;
use App\Domain\Map\Enum\TerrainType;

/**
 * TerrainClusteringService handles terrain clustering coordination
 *
 * Application service that coordinates clustering operations and delegates
 * domain logic to TerrainClusteringDomainService. Handles map iteration
 * and orchestration concerns.
 */
class TerrainClusteringService
{
    public function __construct(
        private readonly HexNeighborService             $neighborService,
        private readonly BaseTerrainGenerationService   $terrainGenerationService,
        private readonly TerrainClusteringDomainService $clusteringDomainService
    ) {
    }

    /**
     * Applies clustering algorithm to create realistic terrain formations
     *
     * @param array $map Current map state
     * @param int $rows Number of rows
     * @param int $cols Number of columns
     * @param int $iterations Number of clustering passes
     * @return array Map with clustering applied
     */
    public function applyClustering(array $map, int $rows, int $cols, int $iterations = 2): array
    {
        for ($iteration = 0; $iteration < $iterations; $iteration++) {
            $newMap = $map;

            for ($row = 0; $row < $rows; $row++) {
                for ($col = 0; $col < $cols; $col++) {
                    $currentTerrain = TerrainType::from($map[$row][$col]['type']);
                    $neighbors = $this->neighborService->getNeighbors($map, $row, $col, $rows, $cols);

                    // Check if we should cluster this terrain type
                    if ($this->clusteringDomainService->shouldTerrainCluster($currentTerrain)) {
                        $sameTerrainCount = $this->clusteringDomainService->countSameTerrainNeighbors($neighbors, $currentTerrain);
                        $totalNeighbors = count($neighbors);

                        // Use domain logic to determine if should spread
                        if ($this->clusteringDomainService->shouldSpreadToNeighbor($currentTerrain, $sameTerrainCount, $totalNeighbors)) {
                            $neighborToConvert = $this->clusteringDomainService->selectNeighborToConvert($neighbors, $currentTerrain);

                            if ($neighborToConvert) {
                                $newMap = $this->convertNeighborTile($newMap, $neighborToConvert, $currentTerrain);
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
     * Gets the clustering configuration for terrain types
     *
     * @return array Terrain clustering probabilities
     */
    public function getClusteringConfiguration(): array
    {
        return $this->clusteringDomainService->getClusteringConfiguration();
    }

    /**
     * Checks if a terrain type should cluster
     *
     * @param string $terrainType Terrain type to check
     * @return bool True if terrain type has clustering behavior
     */
    public function shouldTerrainCluster(string $terrainType): bool
    {
        return $this->clusteringDomainService->shouldTerrainCluster(TerrainType::from($terrainType));
    }

    /**
     * Gets clustering probability for a specific terrain type
     *
     * @param string $terrainType Terrain type to check
     * @return float Clustering probability (0.0 to 1.0)
     */
    public function getClusteringProbability(string $terrainType): float
    {
        return $this->clusteringDomainService->getClusteringProbability(TerrainType::from($terrainType));
    }

    /**
     * Converts neighbor tile to specified terrain type
     *
     * @param array $map Current map state
     * @param array $neighbor Neighbor tile to convert
     * @param TerrainType $targetTerrain Target terrain type
     * @return array Updated map
     */
    private function convertNeighborTile(array $map, array $neighbor, TerrainType $targetTerrain): array
    {
        $neighborRow = $neighbor['coordinates']['row'];
        $neighborCol = $neighbor['coordinates']['col'];

        $map[$neighborRow][$neighborCol] = $this->terrainGenerationService->createTerrainTile(
            $targetTerrain,
            $neighborRow,
            $neighborCol
        );

        return $map;
    }
}
