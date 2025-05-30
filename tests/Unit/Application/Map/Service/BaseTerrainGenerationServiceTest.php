<?php

namespace Tests\Unit\Application\Map\Service;

use App\Application\Map\Service\BaseTerrainGenerationService;
use App\Domain\Map\Service\TerrainGenerationDomainService;
use App\Domain\Map\Enum\TerrainType;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for BaseTerrainGenerationService
 */
class BaseTerrainGenerationServiceTest extends TestCase
{
    private BaseTerrainGenerationService $service;
    private TerrainGenerationDomainService|MockObject $domainService;

    protected function setUp(): void
    {
        $this->domainService = $this->createMock(TerrainGenerationDomainService::class);
        $this->service = new BaseTerrainGenerationService($this->domainService);
    }

    public function testGenerateBaseMap(): void
    {
        $rows = 5;
        $cols = 5;

        $this->domainService->expects($this->exactly($rows * $cols))
            ->method('getWeightedRandomTerrain')
            ->willReturn(TerrainType::PLAINS);

        $this->domainService->expects($this->exactly($rows * $cols))
            ->method('createTerrainTile')
            ->with(TerrainType::PLAINS, $this->anything(), $this->anything())
            ->willReturn(['type' => 'plains', 'name' => 'Plains']);

        $result = $this->service->generateBaseMap($rows, $cols);

        $this->assertIsArray($result);
        $this->assertCount($rows, $result);
        $this->assertCount($cols, $result[0]);
    }

    public function testGetWeightedRandomTerrain(): void
    {
        $expectedTerrain = TerrainType::FOREST;

        $this->domainService->expects($this->once())
            ->method('getWeightedRandomTerrain')
            ->willReturn($expectedTerrain);

        $result = $this->service->getWeightedRandomTerrain();

        $this->assertEquals($expectedTerrain, $result);
    }

    public function testCreateTerrainTile(): void
    {
        $terrainType = TerrainType::MOUNTAIN;
        $row = 3;
        $col = 4;
        $expectedTile = [
            'type' => 'mountain',
            'name' => 'Mountain',
            'properties' => ['movementCost' => 3]
        ];

        $this->domainService->expects($this->once())
            ->method('createTerrainTile')
            ->with($terrainType, $row, $col)
            ->willReturn($expectedTile);

        $result = $this->service->createTerrainTile($terrainType, $row, $col);

        $this->assertEquals($expectedTile, $result);
    }

    public function testGetTerrainWeights(): void
    {
        $expectedWeights = [
            'plains' => 35,
            'forest' => 25,
            'mountain' => 15
        ];

        $this->domainService->expects($this->once())
            ->method('getTerrainWeights')
            ->willReturn($expectedWeights);

        $result = $this->service->getTerrainWeights();

        $this->assertEquals($expectedWeights, $result);
    }

    public function testSetTerrainAt(): void
    {
        $terrainType = TerrainType::WATER;
        $row = 2;
        $col = 3;
        $expectedTile = [
            'type' => 'water',
            'name' => 'Water',
            'properties' => ['movementCost' => 0]
        ];

        $this->domainService->expects($this->once())
            ->method('createTerrainTile')
            ->with($terrainType, $row, $col)
            ->willReturn($expectedTile);

        $result = $this->service->setTerrainAt($terrainType, $row, $col);

        $this->assertEquals($expectedTile, $result);
    }
} 