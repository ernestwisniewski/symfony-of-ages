<?php

namespace App\Domain\Map\Service;

use App\Domain\Map\ValueObject\TerrainType;

class TerrainClusteringDomainService
{
    private const array TERRAIN_CLUSTERS = [
        TerrainType::WATER->value => 0.7,
        TerrainType::FOREST->value => 0.6,
        TerrainType::DESERT->value => 0.6,
        TerrainType::MOUNTAIN->value => 0.5,
        TerrainType::SWAMP->value => 0.4,
        TerrainType::PLAINS->value => 0.3
    ];

    public function shouldTerrainCluster(TerrainType $terrainType): bool
    {
        return isset(self::TERRAIN_CLUSTERS[$terrainType->value]);
    }

    public function getClusteringProbability(TerrainType $terrainType): float
    {
        return self::TERRAIN_CLUSTERS[$terrainType->value] ?? 0.0;
    }

    public function shouldSpreadToNeighbor(TerrainType $terrainType, int $sameNeighborCount, int $totalNeighbors): bool
    {
        if ($sameNeighborCount >= 2) {
            return false;
        }

        $clusterChance = $this->getClusteringProbability($terrainType);
        return mt_rand(1, 100) <= ($clusterChance * 100);
    }

    public function selectNeighborToConvert(array $neighbors, TerrainType $currentTerrain): ?array
    {
        if (empty($neighbors)) {
            return null;
        }

        if (mt_rand(1, 100) > 30) {
            return null;
        }

        $differentNeighbors = array_filter(
            $neighbors,
            fn($neighbor) => TerrainType::from($neighbor['type']) !== $currentTerrain
        );

        if (empty($differentNeighbors)) {
            return null;
        }

        return $differentNeighbors[array_rand($differentNeighbors)];
    }

    private function areTerrainTypesCompatibleForClustering(TerrainType $terrain1, TerrainType $terrain2): bool
    {
        $compatibility = [
            TerrainType::PLAINS->value => [TerrainType::FOREST, TerrainType::DESERT],
            TerrainType::FOREST->value => [TerrainType::PLAINS, TerrainType::MOUNTAIN],
            TerrainType::MOUNTAIN->value => [TerrainType::FOREST, TerrainType::DESERT],
            TerrainType::WATER->value => [TerrainType::SWAMP],
            TerrainType::DESERT->value => [TerrainType::PLAINS, TerrainType::MOUNTAIN],
            TerrainType::SWAMP->value => [TerrainType::WATER, TerrainType::FOREST]
        ];

        return in_array($terrain2, $compatibility[$terrain1->value] ?? []);
    }

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

    public function getClusteringConfiguration(): array
    {
        return self::TERRAIN_CLUSTERS;
    }

    public function isValidClusteringConfiguration(array $clusterConfig): bool
    {
        $terrainValues = array_map(fn($terrain) => $terrain->value, TerrainType::cases());

        $hasValidTerrainTypes = array_all(
            array_keys($clusterConfig),
            fn($terrain) => in_array($terrain, $terrainValues)
        );

        if (!$hasValidTerrainTypes) {
            return false;
        }

        return array_all(
            $clusterConfig,
            fn($probability) => is_float($probability) && $probability >= 0.0 && $probability <= 1.0
        );
    }
}
