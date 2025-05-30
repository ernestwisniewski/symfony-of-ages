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
     * Creates a terrain tile data structure according to domain rules
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
            'name' => $properties->getName(),
            'properties' => $properties->toLegacyArray(),
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
     * Validates terrain weight configuration
     *
     * @param array $weights Custom terrain weights
     * @return bool True if weights are valid
     */
    public function areValidTerrainWeights(array $weights): bool
    {
        foreach ($weights as $terrain => $weight) {
            // Check if terrain type exists
            if (!TerrainType::tryFrom($terrain)) {
                return false;
            }

            // Check if weight is valid
            if (!is_int($weight) || $weight < 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculates ideal terrain distribution percentages
     *
     * @return array Terrain type => ideal percentage
     */
    public function getIdealTerrainDistribution(): array
    {
        $totalWeight = array_sum(self::TERRAIN_WEIGHTS);
        $distribution = [];

        foreach (self::TERRAIN_WEIGHTS as $terrain => $weight) {
            $distribution[$terrain] = ($weight / $totalWeight) * 100;
        }

        return $distribution;
    }
}
