<?php

namespace App\Application\Map\Service;

use App\Domain\Map\Enum\TerrainType;
use App\Domain\Map\Service\TerrainSmoothingDomainService;

/**
 * TerrainSmoothingService handles terrain smoothing coordination
 *
 * Application service that coordinates smoothing operations and delegates
 * domain logic to TerrainSmoothingDomainService. Handles map iteration
 * and orchestration concerns.
 */
class TerrainSmoothingService
{
    public function __construct(
        private readonly HexNeighborService            $neighborService,
        private readonly BaseTerrainGenerationService  $terrainGenerationService,
        private readonly TerrainSmoothingDomainService $smoothingDomainService
    )
    {
    }

    /**
     * Applies compatibility smoothing for natural terrain transitions
     *
     * @param array $map Current map state
     * @param int $rows Number of rows
     * @param int $cols Number of columns
     * @return array Map with compatibility smoothing applied
     */
    public function applyCompatibilitySmoothing(array $map, int $rows, int $cols): array
    {
        $newMap = $map;

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $currentTerrain = TerrainType::from($map[$row][$col]['type']);
                $neighbors = $this->neighborService->getNeighbors($map, $row, $col, $rows, $cols);

                // Extract neighbor terrain types
                $neighborTerrains = array_map(fn($neighbor) => $neighbor['type'], $neighbors);

                // Use domain service to determine if should replace
                if ($this->smoothingDomainService->shouldReplaceForCompatibility($currentTerrain, $neighborTerrains)) {
                    // Count neighbor terrain types
                    $neighborCounts = array_count_values($neighborTerrains);

                    // Use domain service to find best replacement
                    $bestReplacement = $this->smoothingDomainService->findBestReplacementTerrain($neighborCounts);

                    if ($bestReplacement) {
                        $newMap[$row][$col] = $this->terrainGenerationService->createTerrainTile($bestReplacement, $row, $col);
                    }
                }
            }
        }

        return $newMap;
    }

    /**
     * Gets compatibility score between two terrain types using domain service
     *
     * @param string $terrainType1 First terrain type
     * @param string $terrainType2 Second terrain type
     * @return float Compatibility score (0.0 to 1.0)
     */
    public function getCompatibilityScore(string $terrainType1, string $terrainType2): float
    {
        return $this->smoothingDomainService->getCompatibilityScore(
            TerrainType::from($terrainType1),
            TerrainType::from($terrainType2)
        );
    }

    /**
     * Checks if two terrain types are compatible using domain service
     *
     * @param string $terrainType1 First terrain type
     * @param string $terrainType2 Second terrain type
     * @return bool True if terrain types are compatible
     */
    public function areTerrainTypesCompatible(string $terrainType1, string $terrainType2): bool
    {
        return $this->smoothingDomainService->areTerrainTypesCompatible(
            TerrainType::from($terrainType1),
            TerrainType::from($terrainType2)
        );
    }

    /**
     * Gets all compatible terrain types for a given terrain using domain service
     *
     * @param string $terrainType Terrain type to check
     * @return array Array of compatible terrain types as strings
     */
    public function getCompatibleTerrainTypes(string $terrainType): array
    {
        $compatibleTypes = $this->smoothingDomainService->getCompatibleTerrainTypes(TerrainType::from($terrainType));

        // Convert to string array
        return array_map(fn($terrain) => $terrain->value, $compatibleTypes);
    }

    /**
     * Gets the full terrain compatibility matrix using domain service
     *
     * @return array Complete compatibility configuration
     */
    public function getCompatibilityMatrix(): array
    {
        return $this->smoothingDomainService->getCompatibilityMatrix();
    }

    /**
     * Applies targeted smoothing for specific terrain types
     *
     * @param array $map Current map state
     * @param int $rows Number of rows
     * @param int $cols Number of columns
     * @param array $targetTerrains Array of terrain types to focus smoothing on
     * @return array Map with targeted smoothing applied
     */
    public function applyTargetedSmoothing(array $map, int $rows, int $cols, array $targetTerrains): array
    {
        $newMap = $map;

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $currentTerrain = TerrainType::from($map[$row][$col]['type']);

                // Only process if current terrain is in target list
                if (!in_array($currentTerrain->value, $targetTerrains)) {
                    continue;
                }

                $neighbors = $this->neighborService->getNeighbors($map, $row, $col, $rows, $cols);
                $neighborTerrains = array_map(fn($neighbor) => $neighbor['type'], $neighbors);

                // Use domain service to determine if should replace
                if ($this->smoothingDomainService->shouldReplaceForCompatibility($currentTerrain, $neighborTerrains, 0.4)) {
                    $neighborCounts = array_count_values($neighborTerrains);
                    $bestReplacement = $this->smoothingDomainService->findBestReplacementTerrain($neighborCounts);

                    if ($bestReplacement) {
                        $newMap[$row][$col] = $this->terrainGenerationService->createTerrainTile($bestReplacement, $row, $col);
                    }
                }
            }
        }

        return $newMap;
    }
}
