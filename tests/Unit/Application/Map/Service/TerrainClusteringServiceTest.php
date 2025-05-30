<?php

namespace Tests\Unit\Application\Map\Service;

use App\Application\Map\Service\TerrainClusteringService;
use App\Application\Map\Service\HexNeighborService;
use App\Application\Map\Service\BaseTerrainGenerationService;
use App\Domain\Map\Service\TerrainClusteringDomainService;
use App\Domain\Map\Enum\TerrainType;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for TerrainClusteringService
 */
class TerrainClusteringServiceTest extends TestCase
{
    private TerrainClusteringService $service;
    private HexNeighborService|MockObject $neighborService;
    private BaseTerrainGenerationService|MockObject $terrainGenerationService;
    private TerrainClusteringDomainService|MockObject $clusteringDomainService;

    protected function setUp(): void
    {
        $this->neighborService = $this->createMock(HexNeighborService::class);
        $this->terrainGenerationService = $this->createMock(BaseTerrainGenerationService::class);
        $this->clusteringDomainService = $this->createMock(TerrainClusteringDomainService::class);

        $this->service = new TerrainClusteringService(
            $this->neighborService,
            $this->terrainGenerationService,
            $this->clusteringDomainService
        );
    }

    public function testApplyClusteringWithSingleIteration(): void
    {
        $map = [
            [['type' => 'plains'], ['type' => 'forest']],
            [['type' => 'mountain'], ['type' => 'plains']]
        ];
        $rows = 2;
        $cols = 2;
        $iterations = 1;

        $neighbors = [
            ['type' => 'forest', 'coordinates' => ['row' => 0, 'col' => 1]]
        ];

        $this->neighborService->expects($this->exactly($rows * $cols))
            ->method('getNeighbors')
            ->willReturn($neighbors);

        $this->clusteringDomainService->expects($this->exactly($rows * $cols))
            ->method('shouldTerrainCluster')
            ->willReturn(true);

        $this->clusteringDomainService->expects($this->exactly($rows * $cols))
            ->method('countSameTerrainNeighbors')
            ->willReturn(1);

        $this->clusteringDomainService->expects($this->exactly($rows * $cols))
            ->method('shouldSpreadToNeighbor')
            ->willReturn(false);

        $result = $this->service->applyClustering($map, $rows, $cols, $iterations);

        $this->assertIsArray($result);
        $this->assertCount($rows, $result);
        $this->assertCount($cols, $result[0]);
    }

    public function testGetClusteringConfiguration(): void
    {
        $expectedConfig = [
            'forest' => 0.8,
            'mountain' => 0.7,
            'plains' => 0.5
        ];

        $this->clusteringDomainService->expects($this->once())
            ->method('getClusteringConfiguration')
            ->willReturn($expectedConfig);

        $result = $this->service->getClusteringConfiguration();

        $this->assertEquals($expectedConfig, $result);
    }

    public function testShouldTerrainCluster(): void
    {
        $terrainType = 'forest';

        $this->clusteringDomainService->expects($this->once())
            ->method('shouldTerrainCluster')
            ->with(TerrainType::FOREST)
            ->willReturn(true);

        $result = $this->service->shouldTerrainCluster($terrainType);

        $this->assertTrue($result);
    }

    public function testGetClusteringProbability(): void
    {
        $terrainType = 'mountain';
        $expectedProbability = 0.7;

        $this->clusteringDomainService->expects($this->once())
            ->method('getClusteringProbability')
            ->with(TerrainType::MOUNTAIN)
            ->willReturn($expectedProbability);

        $result = $this->service->getClusteringProbability($terrainType);

        $this->assertEquals($expectedProbability, $result);
    }
} 