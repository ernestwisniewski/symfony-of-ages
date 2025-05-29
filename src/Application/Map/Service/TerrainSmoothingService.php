<?php

namespace App\Application\Map\Service;

use App\Domain\Player\Enum\TerrainType;

/**
 * TerrainSmoothingService handles terrain compatibility smoothing
 *
 * Responsible for creating natural terrain transitions by applying
 * compatibility rules between different terrain types.
 * Follows Single Responsibility Principle by focusing only on smoothing logic.
 */
class TerrainSmoothingService
{
    /** @var array Terrain compatibility matrix for neighbor preferences */
    private const array TERRAIN_COMPATIBILITY = [
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

    public function __construct(
        private readonly HexNeighborService           $neighborService,
        private readonly BaseTerrainGenerationService $terrainGenerationService
    )
    {
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
    public function applyCompatibilitySmoothing(array $map, int $rows, int $cols): array
    {
        $newMap = $map;

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $currentTerrain = $map[$row][$col]['type'];
                $neighbors = $this->neighborService->getNeighbors($map, $row, $col, $rows, $cols);

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
                                $newMap[$row][$col] = $this->terrainGenerationService->createTerrainTile($terrainType, $row, $col);
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
     * Gets compatibility score between two terrain types
     *
     * @param string $terrainType1 First terrain type
     * @param string $terrainType2 Second terrain type
     * @return float Compatibility score (0.0 to 1.0)
     */
    public function getCompatibilityScore(string $terrainType1, string $terrainType2): float
    {
        if (isset(self::TERRAIN_COMPATIBILITY[$terrainType1][$terrainType2])) {
            return self::TERRAIN_COMPATIBILITY[$terrainType1][$terrainType2];
        }

        // Check reverse compatibility
        if (isset(self::TERRAIN_COMPATIBILITY[$terrainType2][$terrainType1])) {
            return self::TERRAIN_COMPATIBILITY[$terrainType2][$terrainType1];
        }

        return 0.0; // No compatibility defined
    }

    /**
     * Checks if two terrain types are compatible
     *
     * @param string $terrainType1 First terrain type
     * @param string $terrainType2 Second terrain type
     * @return bool True if terrain types are compatible
     */
    public function areTerrainTypesCompatible(string $terrainType1, string $terrainType2): bool
    {
        return $this->getCompatibilityScore($terrainType1, $terrainType2) > 0.0;
    }

    /**
     * Gets all compatible terrain types for a given terrain
     *
     * @param string $terrainType Terrain type to check
     * @return array Array of compatible terrain types with their scores
     */
    public function getCompatibleTerrainTypes(string $terrainType): array
    {
        $compatible = [];

        // Check direct compatibility
        if (isset(self::TERRAIN_COMPATIBILITY[$terrainType])) {
            $compatible = array_merge($compatible, self::TERRAIN_COMPATIBILITY[$terrainType]);
        }

        // Check reverse compatibility
        foreach (self::TERRAIN_COMPATIBILITY as $otherTerrain => $compatibilities) {
            if (isset($compatibilities[$terrainType])) {
                $compatible[$otherTerrain] = $compatibilities[$terrainType];
            }
        }

        return $compatible;
    }

    /**
     * Gets the full terrain compatibility matrix
     *
     * @return array Complete compatibility configuration
     */
    public function getCompatibilityMatrix(): array
    {
        return self::TERRAIN_COMPATIBILITY;
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
                $currentTerrain = $map[$row][$col]['type'];

                // Only process if current terrain is in target list
                if (!in_array($currentTerrain, $targetTerrains)) {
                    continue;
                }

                $neighbors = $this->neighborService->getNeighbors($map, $row, $col, $rows, $cols);

                // Apply compatibility logic only for targeted terrain
                if (isset(self::TERRAIN_COMPATIBILITY[$currentTerrain])) {
                    $compatibilities = self::TERRAIN_COMPATIBILITY[$currentTerrain];

                    foreach ($neighbors as $neighbor) {
                        $neighborTerrain = $neighbor['type'];

                        if (isset($compatibilities[$neighborTerrain])) {
                            $compatibilityChance = $compatibilities[$neighborTerrain];

                            if (mt_rand(1, 100) <= ($compatibilityChance * 100)) {
                                $terrainType = TerrainType::from($neighborTerrain);
                                $newMap[$row][$col] = $this->terrainGenerationService->createTerrainTile($terrainType, $row, $col);
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $newMap;
    }
}
