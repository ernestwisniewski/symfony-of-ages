<?php

namespace Tests\Unit\Application\Map\Service;

use App\Application\Map\Service\TerrainSmoothingService;
use App\Application\Map\Service\HexNeighborService;
use App\Application\Map\Service\BaseTerrainGenerationService;
use App\Domain\Map\Service\TerrainSmoothingDomainService;
use App\Domain\Map\Enum\TerrainType;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TerrainSmoothingService
 */
class TerrainSmoothingServiceTest extends TestCase
{
    private TerrainSmoothingService $service;
    private HexNeighborService|MockObject $neighborService;
    private BaseTerrainGenerationService|MockObject $terrainGenerationService;
    private TerrainSmoothingDomainService|MockObject $smoothingDomainService;

    protected function setUp(): void
    {
        $this->neighborService = $this->createMock(HexNeighborService::class);
        $this->terrainGenerationService = $this->createMock(BaseTerrainGenerationService::class);
        $this->smoothingDomainService = $this->createMock(TerrainSmoothingDomainService::class);

        $this->service = new TerrainSmoothingService(
            $this->neighborService,
            $this->terrainGenerationService,
            $this->smoothingDomainService
        );
    }

    public function testApplyCompatibilitySmoothing(): void
    {
        $map = [
            [['type' => 'plains'], ['type' => 'forest']],
            [['type' => 'mountain'], ['type' => 'water']]
        ];
        $rows = 2;
        $cols = 2;

        $neighbors = [
            ['type' => 'forest'],
            ['type' => 'mountain']
        ];

        $this->neighborService->expects($this->exactly($rows * $cols))
            ->method('getNeighbors')
            ->willReturn($neighbors);

        $this->smoothingDomainService->expects($this->exactly($rows * $cols))
            ->method('shouldReplaceForCompatibility')
            ->willReturn(false);

        $result = $this->service->applyCompatibilitySmoothing($map, $rows, $cols);

        $this->assertIsArray($result);
        $this->assertCount($rows, $result);
        $this->assertCount($cols, $result[0]);
    }

    public function testApplyCompatibilitySmoothingWithReplacement(): void
    {
        $map = [
            [['type' => 'plains'], ['type' => 'forest']]
        ];
        $rows = 1;
        $cols = 2;

        $neighbors = [['type' => 'forest']];

        $this->neighborService->expects($this->exactly($rows * $cols))
            ->method('getNeighbors')
            ->willReturn($neighbors);

        $this->smoothingDomainService->expects($this->exactly($rows * $cols))
            ->method('shouldReplaceForCompatibility')
            ->willReturn(true);

        $this->smoothingDomainService->expects($this->exactly($rows * $cols))
            ->method('findBestReplacementTerrain')
            ->willReturn(TerrainType::FOREST);

        $this->terrainGenerationService->expects($this->exactly($rows * $cols))
            ->method('createTerrainTile')
            ->willReturn(['type' => 'forest', 'name' => 'Forest']);

        $result = $this->service->applyCompatibilitySmoothing($map, $rows, $cols);

        $this->assertIsArray($result);
    }

    public function testGetCompatibilityScore(): void
    {
        $terrainType1 = 'plains';
        $terrainType2 = 'forest';
        $expectedScore = 0.8;

        $this->smoothingDomainService->expects($this->once())
            ->method('getCompatibilityScore')
            ->with(TerrainType::PLAINS, TerrainType::FOREST)
            ->willReturn($expectedScore);

        $result = $this->service->getCompatibilityScore($terrainType1, $terrainType2);

        $this->assertEquals($expectedScore, $result);
    }

    public function testAreTerrainTypesCompatible(): void
    {
        $terrainType1 = 'mountain';
        $terrainType2 = 'forest';

        $this->smoothingDomainService->expects($this->once())
            ->method('areTerrainTypesCompatible')
            ->with(TerrainType::MOUNTAIN, TerrainType::FOREST)
            ->willReturn(true);

        $result = $this->service->areTerrainTypesCompatible($terrainType1, $terrainType2);

        $this->assertTrue($result);
    }

    public function testGetCompatibleTerrainTypes(): void
    {
        $terrainType = 'plains';
        $compatibleTypes = [TerrainType::FOREST, TerrainType::MOUNTAIN];

        $this->smoothingDomainService->expects($this->once())
            ->method('getCompatibleTerrainTypes')
            ->with(TerrainType::PLAINS)
            ->willReturn($compatibleTypes);

        $result = $this->service->getCompatibleTerrainTypes($terrainType);

        $this->assertEquals(['forest', 'mountain'], $result);
    }

    public function testGetCompatibilityMatrix(): void
    {
        $expectedMatrix = [
            'plains' => ['forest' => 0.8, 'mountain' => 0.6],
            'forest' => ['plains' => 0.8, 'mountain' => 0.7]
        ];

        $this->smoothingDomainService->expects($this->once())
            ->method('getCompatibilityMatrix')
            ->willReturn($expectedMatrix);

        $result = $this->service->getCompatibilityMatrix();

        $this->assertEquals($expectedMatrix, $result);
    }

    public function testApplyTargetedSmoothing(): void
    {
        $map = [
            [['type' => 'plains'], ['type' => 'forest']],
            [['type' => 'mountain'], ['type' => 'water']]
        ];
        $rows = 2;
        $cols = 2;
        $targetTerrains = ['plains', 'forest'];

        $neighbors = [['type' => 'forest']];

        $this->neighborService->method('getNeighbors')
            ->willReturn($neighbors);

        $this->smoothingDomainService->method('shouldReplaceForCompatibility')
            ->willReturn(false);

        $result = $this->service->applyTargetedSmoothing($map, $rows, $cols, $targetTerrains);

        $this->assertIsArray($result);
        $this->assertCount($rows, $result);
    }
} 