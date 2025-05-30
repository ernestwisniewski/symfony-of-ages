<?php

namespace App\Application\Map\Service;

use App\Domain\Map\Enum\TerrainType;
use App\Domain\Map\Service\TerrainGenerationDomainService;

/**
 * BaseTerrainGenerationService handles terrain generation coordination
 *
 * Application service that coordinates terrain generation operations
 * and delegates domain logic to TerrainGenerationDomainService.
 */
class BaseTerrainGenerationService
{
    public function __construct(
        private readonly TerrainGenerationDomainService $terrainDomainService
    )
    {
    }

    /**
     * Generates initial map with weighted random terrain placement
     *
     * @param int $rows Number of rows in the map
     * @param int $cols Number of columns in the map
     * @return array 2D array of terrain tiles
     */
    public function generateBaseMap(int $rows, int $cols): array
    {
        $map = [];

        for ($row = 0; $row < $rows; $row++) {
            $map[$row] = [];
            for ($col = 0; $col < $cols; $col++) {
                $terrainType = $this->terrainDomainService->getWeightedRandomTerrain();
                $map[$row][$col] = $this->terrainDomainService->createTerrainTile($terrainType, $row, $col);
            }
        }

        return $map;
    }

    /**
     * Selects a terrain type based on weighted probabilities
     *
     * @return TerrainType Randomly selected terrain type based on weights
     */
    public function getWeightedRandomTerrain(): TerrainType
    {
        return $this->terrainDomainService->getWeightedRandomTerrain();
    }

    /**
     * Creates a terrain tile data structure
     *
     * @param TerrainType $terrainType The terrain type for this tile
     * @param int $row Row coordinate
     * @param int $col Column coordinate
     * @return array Complete tile data structure
     */
    public function createTerrainTile(TerrainType $terrainType, int $row, int $col): array
    {
        return $this->terrainDomainService->createTerrainTile($terrainType, $row, $col);
    }

    /**
     * Gets the terrain weights configuration
     *
     * @return array Terrain weights array
     */
    public function getTerrainWeights(): array
    {
        return $this->terrainDomainService->getTerrainWeights();
    }

    /**
     * Sets custom terrain at specific position
     *
     * @param TerrainType $terrainType Terrain type to place
     * @param int $row Row coordinate
     * @param int $col Column coordinate
     * @return array Terrain tile data
     */
    public function setTerrainAt(TerrainType $terrainType, int $row, int $col): array
    {
        return $this->terrainDomainService->createTerrainTile($terrainType, $row, $col);
    }
}
