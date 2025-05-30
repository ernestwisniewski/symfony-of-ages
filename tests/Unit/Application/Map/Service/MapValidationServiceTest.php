<?php

namespace Tests\Unit\Application\Map\Service;

use App\Application\Map\Service\MapValidationService;
use App\Application\Map\Service\HexNeighborService;
use App\Application\Map\Service\BaseTerrainGenerationService;
use App\Domain\Map\Enum\TerrainType;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for MapValidationService
 */
class MapValidationServiceTest extends TestCase
{
    private MapValidationService $service;
    private HexNeighborService|MockObject $neighborService;
    private BaseTerrainGenerationService|MockObject $terrainGenerationService;

    protected function setUp(): void
    {
        $this->neighborService = $this->createMock(HexNeighborService::class);
        $this->terrainGenerationService = $this->createMock(BaseTerrainGenerationService::class);
        $this->service = new MapValidationService($this->neighborService, $this->terrainGenerationService);
    }

    public function testCalculateTerrainStatistics(): void
    {
        $map = [
            [['type' => 'plains'], ['type' => 'forest']],
            [['type' => 'plains'], ['type' => 'water']]
        ];
        $rows = 2;
        $cols = 2;

        $result = $this->service->calculateTerrainStatistics($map, $rows, $cols);

        $this->assertIsArray($result);
        $this->assertEquals(50.0, $result['plains']); // 2 out of 4 tiles
        $this->assertEquals(25.0, $result['forest']); // 1 out of 4 tiles
        $this->assertEquals(25.0, $result['water']);  // 1 out of 4 tiles
    }

    public function testValidateMapBalanceWithValidMap(): void
    {
        $map = [
            [['type' => 'plains'], ['type' => 'forest']],
            [['type' => 'mountain'], ['type' => 'plains']]
        ];
        $rows = 2;
        $cols = 2;

        $result = $this->service->validateMapBalance($map, $rows, $cols);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('isValid', $result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertArrayHasKey('suggestions', $result);
        $this->assertArrayHasKey('statistics', $result);
        $this->assertTrue($result['isValid']);
        $this->assertEmpty($result['issues']);
    }

    public function testValidateMapBalanceWithTooMuchWater(): void
    {
        $map = [
            [['type' => 'water'], ['type' => 'water']],
            [['type' => 'water'], ['type' => 'plains']]
        ];
        $rows = 2;
        $cols = 2;

        $result = $this->service->validateMapBalance($map, $rows, $cols);

        $this->assertFalse($result['isValid']);
        $this->assertNotEmpty($result['issues']);
        $this->assertContains('Too much water terrain (75%) - may make map difficult to navigate', $result['issues']);
    }

    public function testPolishMapWithIsolatedTiles(): void
    {
        $map = [
            [['type' => 'plains'], ['type' => 'forest']],
            [['type' => 'mountain'], ['type' => 'plains']]
        ];
        $rows = 2;
        $cols = 2;

        // Mock neighbor service to return terrain counts
        $this->neighborService->method('getNeighborTerrainCounts')
            ->willReturn(['forest' => 1, 'mountain' => 1]);

        $this->terrainGenerationService->method('createTerrainTile')
            ->willReturn(['type' => 'forest', 'name' => 'Forest']);

        $result = $this->service->polishMap($map, $rows, $cols);

        $this->assertIsArray($result);
        $this->assertCount($rows, $result);
        $this->assertCount($cols, $result[0]);
    }

    public function testEnsurePassableTerrainWithSufficientPassableTerrain(): void
    {
        $map = [
            [['type' => 'plains'], ['type' => 'forest']],
            [['type' => 'mountain'], ['type' => 'plains']]
        ];
        $rows = 2;
        $cols = 2;

        $result = $this->service->ensurePassableTerrain($map, $rows, $cols);

        // Should return map unchanged since there's enough passable terrain
        $this->assertEquals($map, $result);
    }

    public function testFixSmallClustersWithMinimumSize(): void
    {
        $map = [
            [['type' => 'plains'], ['type' => 'forest']],
            [['type' => 'mountain'], ['type' => 'water']]
        ];
        $rows = 2;
        $cols = 2;
        $minClusterSize = 2;

        $this->neighborService->method('getNeighborPositions')
            ->willReturn([]);

        $this->neighborService->method('getNeighbors')
            ->willReturn([['type' => 'plains', 'coordinates' => ['row' => 0, 'col' => 0]]]);

        $this->terrainGenerationService->method('createTerrainTile')
            ->willReturn(['type' => 'plains', 'name' => 'Plains']);

        $result = $this->service->fixSmallClusters($map, $rows, $cols, $minClusterSize);

        $this->assertIsArray($result);
        $this->assertCount($rows, $result);
    }
} 