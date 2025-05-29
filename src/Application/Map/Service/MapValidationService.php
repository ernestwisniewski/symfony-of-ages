<?php

namespace App\Application\Map\Service;

use App\Domain\Player\Enum\TerrainType;

/**
 * MapValidationService handles map validation and polishing
 *
 * Responsible for final validation and polishing passes to improve
 * overall map quality by fixing isolated tiles and ensuring coherent
 * terrain distribution. Follows Single Responsibility Principle.
 */
class MapValidationService
{
    /** @var float Maximum water percentage threshold for playability */
    private const float MAX_WATER_PERCENTAGE = 25.0;

    /** @var float Maximum single terrain dominance percentage */
    private const float MAX_DOMINANCE_PERCENTAGE = 60.0;

    /** @var float Minimum terrain percentage for essential terrains */
    private const float MIN_ESSENTIAL_TERRAIN_PERCENTAGE = 5.0;

    /** @var float Minimum passable terrain percentage for map playability */
    private const float MIN_PASSABLE_PERCENTAGE = 70.0;

    /** @var float Percentage of map to convert when fixing passability */
    private const float PASSABILITY_CONVERSION_RATE = 0.1;

    public function __construct(
        private readonly HexNeighborService           $neighborService,
        private readonly BaseTerrainGenerationService $terrainGenerationService
    )
    {
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
    public function polishMap(array $map, int $rows, int $cols): array
    {
        $newMap = $map;

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $currentTerrain = $map[$row][$col]['type'];
                $terrainCounts = $this->neighborService->getNeighborTerrainCounts($map, $row, $col, $rows, $cols);

                // If this tile is isolated (no same terrain neighbors)
                if (!isset($terrainCounts[$currentTerrain]) || $terrainCounts[$currentTerrain] === 0) {
                    $newMap = $this->replaceIsolatedTile($newMap, $row, $col, $terrainCounts);
                }
            }
        }

        return $newMap;
    }

    /**
     * Validates map for game balance and playability
     *
     * @param array $map Map to validate
     * @param int $rows Number of rows
     * @param int $cols Number of columns
     * @return array Validation results with issues and suggestions
     */
    public function validateMapBalance(array $map, int $rows, int $cols): array
    {
        $terrainStats = $this->calculateTerrainStatistics($map, $rows, $cols);
        $issues = [];
        $suggestions = [];

        $this->validateWaterPercentage($terrainStats, $issues, $suggestions);
        $this->validateTerrainVariety($terrainStats, $issues, $suggestions);
        $this->validateEssentialTerrains($terrainStats, $issues, $suggestions);

        return [
            'isValid' => empty($issues),
            'issues' => $issues,
            'suggestions' => $suggestions,
            'statistics' => $terrainStats
        ];
    }

    /**
     * Calculates terrain distribution statistics
     *
     * @param array $map Map to analyze
     * @param int $rows Number of rows
     * @param int $cols Number of columns
     * @return array Terrain distribution percentages
     */
    public function calculateTerrainStatistics(array $map, int $rows, int $cols): array
    {
        $terrainCounts = [];
        $totalTiles = $rows * $cols;

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $terrainType = $map[$row][$col]['type'];
                $terrainCounts[$terrainType] = ($terrainCounts[$terrainType] ?? 0) + 1;
            }
        }

        // Convert to percentages
        $terrainPercentages = [];
        foreach ($terrainCounts as $terrain => $count) {
            $terrainPercentages[$terrain] = round(($count / $totalTiles) * 100, 2);
        }

        return $terrainPercentages;
    }

    /**
     * Fixes isolated terrain clusters smaller than minimum size
     *
     * @param array $map Current map state
     * @param int $rows Number of rows
     * @param int $cols Number of columns
     * @param int $minClusterSize Minimum cluster size to keep
     * @return array Map with small clusters fixed
     */
    public function fixSmallClusters(array $map, int $rows, int $cols, int $minClusterSize = 3): array
    {
        $processedTiles = [];
        $newMap = $map;

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $tileKey = "{$row}_{$col}";

                if (in_array($tileKey, $processedTiles)) {
                    continue;
                }

                $currentTerrain = $map[$row][$col]['type'];
                $cluster = $this->findConnectedCluster($map, $row, $col, $currentTerrain, $rows, $cols);

                // Mark all tiles in this cluster as processed
                foreach ($cluster as $tile) {
                    $processedTiles[] = "{$tile['row']}_{$tile['col']}";
                }

                // If cluster is too small, convert it to most common neighbor terrain
                if (count($cluster) < $minClusterSize) {
                    $newMap = $this->replaceSmallCluster($newMap, $cluster, $map, $rows, $cols);
                }
            }
        }

        return $newMap;
    }

    /**
     * Ensures minimum passable terrain for player movement
     *
     * @param array $map Current map state
     * @param int $rows Number of rows
     * @param int $cols Number of columns
     * @return array Map with ensured passable terrain
     */
    public function ensurePassableTerrain(array $map, int $rows, int $cols): array
    {
        $passablePercentage = $this->calculatePassableTerrainPercentage($map, $rows, $cols);

        if ($passablePercentage < self::MIN_PASSABLE_PERCENTAGE) {
            return $this->convertWaterToPlains($map, $rows, $cols);
        }

        return $map;
    }

    // Private helper methods

    /**
     * Replaces an isolated tile with the most common neighbor terrain
     */
    private function replaceIsolatedTile(array $map, int $row, int $col, array $terrainCounts): array
    {
        if (!empty($terrainCounts)) {
            $mostCommonTerrain = array_keys($terrainCounts, max($terrainCounts))[0];
            $terrainType = TerrainType::from($mostCommonTerrain);
            $map[$row][$col] = $this->terrainGenerationService->createTerrainTile($terrainType, $row, $col);
        }

        return $map;
    }

    /**
     * Validates water percentage for playability
     */
    private function validateWaterPercentage(array $terrainStats, array &$issues, array &$suggestions): void
    {
        $waterPercentage = $terrainStats[TerrainType::WATER->value] ?? 0;

        if ($waterPercentage > self::MAX_WATER_PERCENTAGE) {
            $issues[] = "Too much water terrain ({$waterPercentage}%) - may make map difficult to navigate";
            $suggestions[] = "Consider reducing water generation weight or improving clustering";
        }
    }

    /**
     * Validates terrain variety and dominance
     */
    private function validateTerrainVariety(array $terrainStats, array &$issues, array &$suggestions): void
    {
        $dominantTerrain = array_keys($terrainStats, max($terrainStats))[0];
        $dominantPercentage = $terrainStats[$dominantTerrain];

        if ($dominantPercentage > self::MAX_DOMINANCE_PERCENTAGE) {
            $issues[] = "Map lacks terrain variety - {$dominantTerrain} dominates {$dominantPercentage}%";
            $suggestions[] = "Adjust terrain weights for better balance";
        }
    }

    /**
     * Validates presence of essential terrains
     */
    private function validateEssentialTerrains(array $terrainStats, array &$issues, array &$suggestions): void
    {
        $essentialTerrains = [TerrainType::PLAINS->value, TerrainType::FOREST->value];

        foreach ($essentialTerrains as $terrain) {
            $percentage = $terrainStats[$terrain] ?? 0;

            if ($percentage < self::MIN_ESSENTIAL_TERRAIN_PERCENTAGE) {
                $issues[] = "Insufficient {$terrain} terrain (needed for gameplay balance)";
                $suggestions[] = "Ensure minimum {$terrain} generation";
            }
        }
    }

    /**
     * Replaces a small cluster with appropriate terrain
     */
    private function replaceSmallCluster(array $map, array $cluster, array $originalMap, int $rows, int $cols): array
    {
        $replacementTerrain = $this->findBestReplacementTerrain($originalMap, $cluster, $rows, $cols);

        if ($replacementTerrain) {
            $terrainType = TerrainType::from($replacementTerrain);
            foreach ($cluster as $tile) {
                $map[$tile['row']][$tile['col']] = $this->terrainGenerationService->createTerrainTile(
                    $terrainType,
                    $tile['row'],
                    $tile['col']
                );
            }
        }

        return $map;
    }

    /**
     * Converts water tiles to plains for better passability
     */
    private function convertWaterToPlains(array $map, int $rows, int $cols): array
    {
        $newMap = $map;
        $conversionsNeeded = intval(($rows * $cols) * self::PASSABILITY_CONVERSION_RATE);
        $conversions = 0;

        for ($row = 0; $row < $rows && $conversions < $conversionsNeeded; $row++) {
            for ($col = 0; $col < $cols && $conversions < $conversionsNeeded; $col++) {
                if ($map[$row][$col]['type'] === TerrainType::WATER->value) {
                    $newMap[$row][$col] = $this->terrainGenerationService->createTerrainTile(
                        TerrainType::PLAINS,
                        $row,
                        $col
                    );
                    $conversions++;
                }
            }
        }

        return $newMap;
    }

    /**
     * Finds all connected tiles of the same terrain type
     *
     * @param array $map Current map state
     * @param int $startRow Starting row
     * @param int $startCol Starting column
     * @param string $terrainType Terrain type to match
     * @param int $maxRows Total rows
     * @param int $maxCols Total columns
     * @return array Array of connected tile coordinates
     */
    private function findConnectedCluster(array $map, int $startRow, int $startCol, string $terrainType, int $maxRows, int $maxCols): array
    {
        $cluster = [];
        $toProcess = [['row' => $startRow, 'col' => $startCol]];
        $processed = [];

        while (!empty($toProcess)) {
            $current = array_pop($toProcess);
            $key = "{$current['row']}_{$current['col']}";

            if (in_array($key, $processed)) {
                continue;
            }

            $processed[] = $key;

            if ($map[$current['row']][$current['col']]['type'] === $terrainType) {
                $cluster[] = $current;

                // Add neighbors to process
                $neighborPositions = $this->neighborService->getNeighborPositions(
                    $current['row'],
                    $current['col'],
                    $maxRows,
                    $maxCols
                );

                foreach ($neighborPositions as $neighbor) {
                    $neighborKey = "{$neighbor['row']}_{$neighbor['col']}";
                    if (!in_array($neighborKey, $processed)) {
                        $toProcess[] = $neighbor;
                    }
                }
            }
        }

        return $cluster;
    }

    /**
     * Finds the best terrain type to replace a small cluster
     *
     * @param array $map Current map state
     * @param array $cluster Cluster tiles to replace
     * @param int $maxRows Total rows
     * @param int $maxCols Total columns
     * @return string|null Best replacement terrain type
     */
    private function findBestReplacementTerrain(array $map, array $cluster, int $maxRows, int $maxCols): ?string
    {
        $neighborTerrainCounts = [];

        foreach ($cluster as $tile) {
            $neighbors = $this->neighborService->getNeighbors($map, $tile['row'], $tile['col'], $maxRows, $maxCols);

            foreach ($neighbors as $neighbor) {
                if (!$this->isTileInCluster($neighbor, $cluster)) {
                    $terrainType = $neighbor['type'];
                    $neighborTerrainCounts[$terrainType] = ($neighborTerrainCounts[$terrainType] ?? 0) + 1;
                }
            }
        }

        return empty($neighborTerrainCounts)
            ? null
            : array_keys($neighborTerrainCounts, max($neighborTerrainCounts))[0];
    }

    /**
     * Checks if a tile is part of the given cluster
     */
    private function isTileInCluster(array $tile, array $cluster): bool
    {
        foreach ($cluster as $clusterTile) {
            if ($tile['coordinates']['row'] === $clusterTile['row'] &&
                $tile['coordinates']['col'] === $clusterTile['col']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculates percentage of passable terrain
     *
     * @param array $map Map to analyze
     * @param int $rows Number of rows
     * @param int $cols Number of columns
     * @return float Percentage of passable terrain
     */
    private function calculatePassableTerrainPercentage(array $map, int $rows, int $cols): float
    {
        $passableCount = 0;
        $totalTiles = $rows * $cols;

        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $terrainType = TerrainType::from($map[$row][$col]['type']);
                if ($terrainType->getProperties()['movementCost'] > 0) {
                    $passableCount++;
                }
            }
        }

        return ($passableCount / $totalTiles) * 100;
    }
}
