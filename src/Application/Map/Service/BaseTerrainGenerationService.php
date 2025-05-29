<?php

namespace App\Application\Map\Service;

use App\Domain\Player\Enum\TerrainType;

/**
 * BaseTerrainGenerationService handles basic terrain generation
 *
 * Responsible for initial terrain placement using weighted probabilities
 * and creating terrain tile data structures. Follows Single Responsibility
 * Principle by focusing only on base terrain generation logic.
 */
class BaseTerrainGenerationService
{
    /** @var array Weighted probabilities for base terrain generation */
    private const array TERRAIN_WEIGHTS = [
        TerrainType::PLAINS->value => 35,    // Most common - basic grassland
        TerrainType::FOREST->value => 25,    // Common - wooded areas
        TerrainType::MOUNTAIN->value => 15,  // Moderate - elevated terrain
        TerrainType::WATER->value => 10,     // Moderate - rivers and lakes
        TerrainType::DESERT->value => 10,    // Moderate - arid regions
        TerrainType::SWAMP->value => 5       // Rare - marshy areas
    ];

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
                $terrainType = $this->getWeightedRandomTerrain();
                $map[$row][$col] = $this->createTerrainTile($terrainType, $row, $col);
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
        $totalWeight = array_sum(self::TERRAIN_WEIGHTS);
        $random = mt_rand(1, $totalWeight);
        $currentWeight = 0;

        foreach (self::TERRAIN_WEIGHTS as $terrainValue => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return TerrainType::from($terrainValue);
            }
        }

        // Fallback to plains if something goes wrong
        return TerrainType::PLAINS;
    }

    /**
     * Creates a terrain tile data structure
     *
     * @param TerrainType $terrainType The terrain type for this tile
     * @param int $row Row coordinate
     * @param int $col Column coordinate
     *
     * @return array Complete tile data structure
     */
    public function createTerrainTile(TerrainType $terrainType, int $row, int $col): array
    {
        $properties = $terrainType->getProperties();

        return [
            'type' => $terrainType->value,
            'name' => $properties['name'],
            'properties' => $properties,
            'coordinates' => [
                'row' => $row,
                'col' => $col
            ]
        ];
    }

    /**
     * Gets the terrain weights configuration
     *
     * @return array Terrain weights array
     */
    public function getTerrainWeights(): array
    {
        return self::TERRAIN_WEIGHTS;
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
        return $this->createTerrainTile($terrainType, $row, $col);
    }
} 