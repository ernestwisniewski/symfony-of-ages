<?php

namespace App\Service;

use App\Enum\TerrainType;

/**
 * MapGenerator service for creating realistic hexagonal map data
 * 
 * Generates 2D arrays of terrain data using weighted probabilities and clustering
 * algorithms to create natural-looking terrain formations. Supports multiple
 * generation passes for realistic terrain distribution and geographic features.
 */
class MapGenerator
{
    /** @var array Weighted probabilities for base terrain generation */
    private const TERRAIN_WEIGHTS = [
        TerrainType::PLAINS->value => 35,    // Most common - basic grassland
        TerrainType::FOREST->value => 25,    // Common - wooded areas
        TerrainType::MOUNTAIN->value => 15,  // Moderate - elevated terrain
        TerrainType::WATER->value => 10,     // Moderate - rivers and lakes
        TerrainType::DESERT->value => 10,    // Moderate - arid regions
        TerrainType::SWAMP->value => 5       // Rare - marshy areas
    ];

    /** @var array Clustering probabilities for terrain types to appear near themselves */
    private const TERRAIN_CLUSTERS = [
        TerrainType::WATER->value => 0.7,    // High clustering - water bodies
        TerrainType::FOREST->value => 0.6,   // Good clustering - forest patches
        TerrainType::DESERT->value => 0.6,   // Good clustering - desert regions
        TerrainType::MOUNTAIN->value => 0.5, // Moderate clustering - mountain ranges
        TerrainType::SWAMP->value => 0.4,    // Low clustering - scattered swamps
        TerrainType::PLAINS->value => 0.3    // Minimal clustering - fills gaps
    ];

    /** @var array Terrain compatibility matrix for neighbor preferences */
    private const TERRAIN_COMPATIBILITY = [
        TerrainType::WATER->value => [
            TerrainType::SWAMP->value => 0.8,   // Swamps near water
            TerrainType::PLAINS->value => 0.6,  // Plains near water
            TerrainType::FOREST->value => 0.4,  // Some forests near water
        ],
        TerrainType::MOUNTAIN->value => [
            TerrainType::FOREST->value => 0.7,  // Forests on mountain slopes
            TerrainType::PLAINS->value => 0.5,  // Plains at mountain base
            TerrainType::DESERT->value => 0.3,  // Some desert mountains
        ],
        TerrainType::FOREST->value => [
            TerrainType::PLAINS->value => 0.8,  // Forest edges blend to plains
            TerrainType::SWAMP->value => 0.4,   // Some swampy forests
        ],
        TerrainType::DESERT->value => [
            TerrainType::PLAINS->value => 0.6,  // Desert transitions to plains
            TerrainType::MOUNTAIN->value => 0.4, // Desert mountains
        ],
        TerrainType::SWAMP->value => [
            TerrainType::WATER->value => 0.9,   // Swamps love water
            TerrainType::FOREST->value => 0.5,  // Swampy forests
        ]
    ];

    /**
     * Generates a realistic map with weighted terrain distribution and clustering
     * 
     * Creates a natural-looking hexagonal map through multiple generation phases:
     * 1. Initial weighted random placement
     * 2. Clustering enhancement based on terrain preferences  
     * 3. Compatibility smoothing for realistic transitions
     * 4. Final polish pass for edge cases
     * 
     * @param int $rows Number of rows in the map grid
     * @param int $cols Number of columns in the map grid
     * 
     * @return array 2D array of terrain tiles with realistic distribution
     */
    public function generateMap(int $rows, int $cols): array
    {
        $map = [];

        // Phase 1: Initialize map with weighted random terrain
        for ($row = 0; $row < $rows; $row++) {
            $map[$row] = [];
            for ($col = 0; $col < $cols; $col++) {
                $terrainType = $this->getWeightedRandomTerrain();
                $map[$row][$col] = $this->createTerrainTile($terrainType, $row, $col);
            }
        }

        // Phase 2: Apply clustering to create terrain formations
        $map = $this->applyClustering($map, $rows, $cols, 2);

        // Phase 3: Apply compatibility smoothing for natural transitions
        $map = $this->applyCompatibilitySmoothing($map, $rows, $cols);

        // Phase 4: Final polish pass to fix isolated tiles
        $map = $this->polishMap($map, $rows, $cols);

        return $map;
    }

    /**
     * Selects a terrain type based on weighted probabilities
     * 
     * @return TerrainType Randomly selected terrain type based on weights
     */
    private function getWeightedRandomTerrain(): TerrainType
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
    private function createTerrainTile(TerrainType $terrainType, int $row, int $col): array
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
     * Applies clustering algorithm to create realistic terrain formations
     * 
     * @param array $map Current map state
     * @param int $rows Number of rows
     * @param int $cols Number of columns
     * @param int $iterations Number of clustering passes
     * 
     * @return array Map with clustering applied
     */
    private function applyClustering(array $map, int $rows, int $cols, int $iterations = 2): array
    {
        for ($iteration = 0; $iteration < $iterations; $iteration++) {
            $newMap = $map;
            
            for ($row = 0; $row < $rows; $row++) {
                for ($col = 0; $col < $cols; $col++) {
                    $currentTerrain = $map[$row][$col]['type'];
                    $neighbors = $this->getNeighbors($map, $row, $col, $rows, $cols);
                    
                    // Check if we should cluster this terrain type
                    if (isset(self::TERRAIN_CLUSTERS[$currentTerrain])) {
                        $clusterChance = self::TERRAIN_CLUSTERS[$currentTerrain];
                        $sameTerrainCount = 0;
                        $totalNeighbors = count($neighbors);
                        
                        foreach ($neighbors as $neighbor) {
                            if ($neighbor['type'] === $currentTerrain) {
                                $sameTerrainCount++;
                            }
                        }
                        
                        // If this terrain should cluster and we have few same neighbors
                        if ($totalNeighbors > 0 && $sameTerrainCount < 2) {
                            $shouldCluster = mt_rand(1, 100) <= ($clusterChance * 100);
                            
                            if ($shouldCluster && $totalNeighbors > 0) {
                                // Find a neighbor of the same type to spread to
                                foreach ($neighbors as $neighbor) {
                                    if ($neighbor['type'] === $currentTerrain) {
                                        continue; // Already same type
                                    }
                                    
                                    // Random chance to convert neighbor
                                    if (mt_rand(1, 100) <= 30) {
                                        $terrainType = TerrainType::from($currentTerrain);
                                        $newMap[$neighbor['coordinates']['row']][$neighbor['coordinates']['col']] = 
                                            $this->createTerrainTile($terrainType, $neighbor['coordinates']['row'], $neighbor['coordinates']['col']);
                                        break;
                                    }
                                }
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
     * Applies compatibility smoothing for natural terrain transitions
     * 
     * @param array $map Current map state
     * @param int $rows Number of rows
     * @param int $cols Number of columns
     * 
     * @return array Map with compatibility smoothing applied
     */
    private function applyCompatibilitySmoothing(array $map, int $rows, int $cols): array
    {
        $newMap = $map;
        
        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $currentTerrain = $map[$row][$col]['type'];
                $neighbors = $this->getNeighbors($map, $row, $col, $rows, $cols);
                
                // Check compatibility with neighbors
                if (isset(self::TERRAIN_COMPATIBILITY[$currentTerrain])) {
                    $compatibilities = self::TERRAIN_COMPATIBILITY[$currentTerrain];
                    
                    foreach ($neighbors as $neighbor) {
                        $neighborTerrain = $neighbor['type'];
                        
                        // If this terrain type is compatible with current
                        if (isset($compatibilities[$neighborTerrain])) {
                            $compatibilityChance = $compatibilities[$neighborTerrain];
                            
                            if (mt_rand(1, 100) <= ($compatibilityChance * 100)) {
                                $terrainType = TerrainType::from($neighborTerrain);
                                $newMap[$row][$col] = $this->createTerrainTile($terrainType, $row, $col);
                                break; // Only one conversion per tile
                            }
                        }
                    }
                }
            }
        }
        
        return $newMap;
    }

    /**
     * Final polish pass to fix isolated tiles and improve overall map quality
     * 
     * @param array $map Current map state
     * @param int $rows Number of rows
     * @param int $cols Number of columns
     * 
     * @return array Polished final map
     */
    private function polishMap(array $map, int $rows, int $cols): array
    {
        $newMap = $map;
        
        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $currentTerrain = $map[$row][$col]['type'];
                $neighbors = $this->getNeighbors($map, $row, $col, $rows, $cols);
                
                // Count terrain types in neighborhood
                $terrainCounts = [];
                foreach ($neighbors as $neighbor) {
                    $terrainType = $neighbor['type'];
                    $terrainCounts[$terrainType] = ($terrainCounts[$terrainType] ?? 0) + 1;
                }
                
                // If this tile is isolated (no same terrain neighbors)
                if (!isset($terrainCounts[$currentTerrain]) || $terrainCounts[$currentTerrain] === 0) {
                    // Convert to most common neighbor terrain type
                    if (!empty($terrainCounts)) {
                        $mostCommonTerrain = array_keys($terrainCounts, max($terrainCounts))[0];
                        $terrainType = TerrainType::from($mostCommonTerrain);
                        $newMap[$row][$col] = $this->createTerrainTile($terrainType, $row, $col);
                    }
                }
            }
        }
        
        return $newMap;
    }

    /**
     * Gets neighboring tiles for a given position using hexagonal grid logic
     * 
     * @param array $map Current map state
     * @param int $row Current row
     * @param int $col Current column  
     * @param int $maxRows Total rows in map
     * @param int $maxCols Total columns in map
     * 
     * @return array Array of neighboring tile data
     */
    private function getNeighbors(array $map, int $row, int $col, int $maxRows, int $maxCols): array
    {
        $neighbors = [];
        
        // Hexagonal neighbors (6 directions)
        $directions = $this->getHexDirections($row);
        
        foreach ($directions as $direction) {
            $newRow = $row + $direction[0];
            $newCol = $col + $direction[1];
            
            // Check bounds
            if ($newRow >= 0 && $newRow < $maxRows && $newCol >= 0 && $newCol < $maxCols) {
                $neighbors[] = $map[$newRow][$newCol];
            }
        }
        
        return $neighbors;
    }

    /**
     * Gets direction vectors for hexagonal neighbors
     * 
     * @param int $row Current row (needed for odd/even row offset)
     * 
     * @return array Array of [row_offset, col_offset] direction vectors
     */
    private function getHexDirections(int $row): array
    {
        // Hexagonal grid directions depend on whether row is odd or even
        if ($row % 2 === 0) {
            // Even row
            return [
                [-1, -1], [-1, 0],  // Top-left, Top-right
                [0, -1],  [0, 1],   // Left, Right
                [1, -1],  [1, 0]    // Bottom-left, Bottom-right
            ];
        } else {
            // Odd row
            return [
                [-1, 0],  [-1, 1],  // Top-left, Top-right
                [0, -1],  [0, 1],   // Left, Right
                [1, 0],   [1, 1]    // Bottom-left, Bottom-right
            ];
        }
    }
} 