<?php

namespace App\Domain\Map\Service;

use App\Domain\Map\ValueObject\TerrainType;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
use App\Domain\Unit\ValueObject\UnitType;
use App\UI\Map\ViewModel\MapTileView;

class MapGeneratorService
{
    public function __construct(
        private readonly TerrainGenerationService $terrainGenerationService,
        private readonly TerrainClusteringService $terrainClusteringService,
        private readonly TerrainSmoothingService  $terrainSmoothingService,
        private readonly TerrainAnalyzer          $terrainAnalyzer
    )
    {
    }

    public function generateAdvancedMap(int $width, int $height, int $smoothingIterations = 2): array
    {
        $map = $this->generateInitialTerrain($width, $height);
        $map = $this->applyTerrainClustering($map);
        for ($i = 0; $i < $smoothingIterations; $i++) {
            $map = $this->applyTerrainSmoothing($map);
        }
        return $this->convertToMapTileViews($map);
    }

    private function generateInitialTerrain(int $width, int $height): array
    {
        $map = [];
        for ($row = 0; $row < $height; $row++) {
            $map[$row] = [];
            for ($col = 0; $col < $width; $col++) {
                $terrainType = $this->terrainGenerationService->getWeightedRandomTerrain();
                $map[$row][$col] = $this->terrainGenerationService->createTerrainTile($terrainType, $row, $col);
            }
        }
        return $map;
    }

    private function applyTerrainClustering(array $map): array
    {
        $height = count($map);
        $width = count($map[0]);
        $newMap = $map;
        for ($row = 0; $row < $height; $row++) {
            for ($col = 0; $col < $width; $col++) {
                $currentTerrain = TerrainType::from($map[$row][$col]['type']);
                $neighbors = $this->getNeighbors($map, $row, $col);
                if ($this->terrainClusteringService->shouldTerrainCluster($currentTerrain)) {
                    $sameNeighborCount = $this->terrainClusteringService->countSameTerrainNeighbors($neighbors, $currentTerrain);
                    if ($this->terrainClusteringService->shouldSpreadToNeighbor($currentTerrain, $sameNeighborCount, count($neighbors))) {
                        $neighborToConvert = $this->terrainClusteringService->selectNeighborToConvert($neighbors, $currentTerrain);
                        if ($neighborToConvert !== null) {
                            $newMap[$neighborToConvert['row']][$neighborToConvert['col']] = $this->terrainGenerationService->createTerrainTile(
                                $currentTerrain,
                                $neighborToConvert['row'],
                                $neighborToConvert['col']
                            );
                        }
                    }
                }
            }
        }
        return $newMap;
    }

    private function applyTerrainSmoothing(array $map): array
    {
        $height = count($map);
        $width = count($map[0]);
        $newMap = $map;
        for ($row = 0; $row < $height; $row++) {
            for ($col = 0; $col < $width; $col++) {
                $currentTerrain = TerrainType::from($map[$row][$col]['type']);
                $neighborTerrains = $this->getNeighborTerrainTypes($map, $row, $col);
                if ($this->terrainSmoothingService->shouldReplaceForCompatibility($currentTerrain, $neighborTerrains)) {
                    $bestReplacement = $this->terrainSmoothingService->findBestReplacementTerrain($neighborTerrains);
                    if ($bestReplacement !== null) {
                        $newMap[$row][$col] = $this->terrainGenerationService->createTerrainTile($bestReplacement, $row, $col);
                    }
                }
            }
        }
        return $newMap;
    }

    private function getNeighbors(array $map, int $row, int $col): array
    {
        $neighbors = [];
        $height = count($map);
        $width = count($map[0]);
        $directions = [
            [-1, -1], [-1, 0], [-1, 1],
            [0, -1], [0, 1],
            [1, -1], [1, 0], [1, 1]
        ];
        foreach ($directions as [$dRow, $dCol]) {
            $newRow = $row + $dRow;
            $newCol = $col + $dCol;
            if ($newRow >= 0 && $newRow < $height && $newCol >= 0 && $newCol < $width) {
                $neighbors[] = [
                    'row' => $newRow,
                    'col' => $newCol,
                    'type' => $map[$newRow][$newCol]['type']
                ];
            }
        }
        return $neighbors;
    }

    private function getNeighborTerrainTypes(array $map, int $row, int $col): array
    {
        $neighbors = $this->getNeighbors($map, $row, $col);
        $terrainTypes = [];
        foreach ($neighbors as $neighbor) {
            $type = $neighbor['type'];
            $terrainTypes[$type] = ($terrainTypes[$type] ?? 0) + 1;
        }
        return $terrainTypes;
    }

    private function convertToMapTileViews(array $map): array
    {
        $tiles = [];
        foreach ($map as $row) {
            foreach ($row as $tile) {
                $tiles[] = new MapTileView(
                    $tile['coordinates']['col'],
                    $tile['coordinates']['row'],
                    $tile['type'],
                    false
                );
            }
        }
        return $tiles;
    }

    public function generateTiles(int $width, int $height): array
    {
        return $this->generateAdvancedMap($width, $height, 0);
    }

    public function getStartingPosition(PlayerId $playerId, string $unitType): Position
    {
        $playerIndex = $this->getPlayerIndex($playerId);
        $baseX = 50;
        $baseY = 50;
        switch ($playerIndex) {
            case 0:
                $x = $baseX - 12;
                $y = $baseY - 12;
                break;
            case 1:
                $x = $baseX + 12;
                $y = $baseY - 12;
                break;
            case 2:
                $x = $baseX - 12;
                $y = $baseY + 12;
                break;
            case 3:
                $x = $baseX + 12;
                $y = $baseY + 12;
                break;
            default:
                $x = $baseX + ($playerIndex * 15);
                $y = $baseY;
        }
        if ($unitType === UnitType::SETTLER->value) {
            $x += 1;
            $y += 1;
        }
        return new Position($x, $y);
    }

    private function getPlayerIndex(PlayerId $playerId): int
    {
        $hash = crc32((string)$playerId);
        return $hash % 4;
    }

    public function analyzeMapQuality(array $map): array
    {
        $terrainCounts = [];
        $totalTiles = 0;
        foreach ($map as $tile) {
            $terrainType = TerrainType::from($tile->terrain);
            $terrainCounts[$terrainType->value] = ($terrainCounts[$terrainType->value] ?? 0) + 1;
            $totalTiles++;
        }
        $analysis = [
            'total_tiles' => $totalTiles,
            'terrain_distribution' => $terrainCounts,
            'terrain_analysis' => []
        ];
        foreach ($terrainCounts as $terrainType => $count) {
            $terrain = TerrainType::from($terrainType);
            $analysis['terrain_analysis'][$terrainType] = $this->terrainAnalyzer->getComprehensiveAnalysis($terrain);
        }
        return $analysis;
    }
}
