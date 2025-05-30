<?php

namespace App\Domain\Map\Service;

use App\Domain\Map\Enum\TerrainType;

/**
 * TerrainGenerationDomainService handles core terrain generation domain logic
 *
 * Pure domain service that encapsulates business rules for terrain
 * generation, terrain weights, and terrain tile creation logic.
 * Contains domain knowledge about terrain distribution.
 */
class TerrainGenerationDomainService
{
    /** @var array Terrain generation weights for random selection */
    private const array TERRAIN_WEIGHTS = [
        TerrainType::PLAINS->value => 35,    // Most common - basic grassland
        TerrainType::FOREST->value => 25,    // Common - wooded areas
        TerrainType::MOUNTAIN->value => 15,  // Moderate - elevated terrain
        TerrainType::WATER->value => 10,     // Moderate - rivers and lakes
        TerrainType::DESERT->value => 10,    // Moderate - arid regions
        TerrainType::SWAMP->value => 5       // Rare - marshy areas
    ];

    // Modern property hooks for configuration access
    public array $terrainWeights {
        get => self::TERRAIN_WEIGHTS;
    }

    public int $totalWeight {
        get => array_sum(self::TERRAIN_WEIGHTS);
    }

    public array $idealDistribution {
        get => array_map(
            fn($weight) => round($weight, 2),
            self::TERRAIN_WEIGHTS
        );
    }

    /**
     * Selects terrain type based on weighted probabilities using modern array functions
     *
     * @return TerrainType Randomly selected terrain type
     */
    public function getWeightedRandomTerrain(): TerrainType
    {
        $totalWeight = $this->totalWeight;
        $random = mt_rand(1, $totalWeight);
        $currentWeight = 0;

        // Traditional approach since array_find is still experimental
        foreach (self::TERRAIN_WEIGHTS as $terrainValue => $weight) {
            $currentWeight += $weight;
            if ($currentWeight >= $random) {
                return TerrainType::from($terrainValue);
            }
        }

        // Fallback to plains if something goes wrong
        return TerrainType::PLAINS;
    }

    /**
     * Creates a complete terrain tile data structure
     *
     * @param TerrainType $terrainType The terrain type for this tile
     * @param int $row Row coordinate
     * @param int $col Column coordinate
     * @return array Complete tile data structure
     */
    public function createTerrainTile(TerrainType $terrainType, int $row, int $col): array
    {
        $properties = $terrainType->getProperties();

        return [
            'type' => $terrainType->value,
            'name' => $properties->name,
            'coordinates' => ['row' => $row, 'col' => $col],
            'properties' => $properties->toArray()
        ];
    }

    /**
     * Gets terrain generation weights for external analysis
     *
     * @return array Terrain weights array
     */
    public function getTerrainWeights(): array
    {
        return $this->terrainWeights;
    }

    /**
     * Validates terrain weight configuration
     *
     * @param array $weights Weights to validate
     * @return bool True if weights are valid
     */
    public function areValidTerrainWeights(array $weights): bool
    {
        if (empty($weights)) {
            return false;
        }

        $terrainValues = array_map(fn($terrain) => $terrain->value, TerrainType::cases());

        // Check if all terrain types are valid using PHP 8.4 array_all
        $hasValidTerrainTypes = array_all(
            array_keys($weights),
            fn($terrain) => in_array($terrain, $terrainValues)
        );

        if (!$hasValidTerrainTypes) {
            return false;
        }

        // Check if all weights are valid integers (allow zero but not negative) using PHP 8.4 array_all
        return array_all(
            $weights,
            fn($weight) => is_int($weight) && $weight >= 0
        );
    }

    /**
     * Gets ideal terrain distribution percentages for validation
     *
     * @return array Ideal distribution percentages
     */
    public function getIdealTerrainDistribution(): array
    {
        return $this->idealDistribution;
    }
}
