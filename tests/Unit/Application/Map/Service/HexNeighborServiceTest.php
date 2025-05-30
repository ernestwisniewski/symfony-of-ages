<?php

namespace Tests\Unit\Application\Map\Service;

use App\Application\Map\Service\HexNeighborService;
use App\Domain\Shared\Service\HexGridService;
use App\Domain\Player\ValueObject\Position;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for HexNeighborService
 */
class HexNeighborServiceTest extends TestCase
{
    private HexNeighborService $service;
    private HexGridService|MockObject $hexGridService;

    protected function setUp(): void
    {
        $this->hexGridService = $this->createMock(HexGridService::class);
        $this->service = new HexNeighborService($this->hexGridService);
    }

    public function testGetNeighbors(): void
    {
        $map = [
            [['type' => 'plains'], ['type' => 'forest']],
            [['type' => 'mountain'], ['type' => 'water']]
        ];
        $row = 0;
        $col = 0;
        $maxRows = 2;
        $maxCols = 2;

        $expectedNeighbors = [
            ['type' => 'forest', 'coordinates' => ['row' => 0, 'col' => 1]]
        ];

        $this->hexGridService->expects($this->once())
            ->method('getNeighborTiles')
            ->with(
                $this->equalTo($map),
                $this->equalTo(new Position($row, $col)),
                $this->equalTo($maxRows),
                $this->equalTo($maxCols)
            )
            ->willReturn($expectedNeighbors);

        $result = $this->service->getNeighbors($map, $row, $col, $maxRows, $maxCols);

        $this->assertEquals($expectedNeighbors, $result);
    }

    public function testGetHexDirections(): void
    {
        $row = 0;
        $expectedDirections = [[-1, 0], [-1, 1], [0, -1], [0, 1], [1, 0], [1, 1]];

        $this->hexGridService->expects($this->once())
            ->method('getHexDirections')
            ->with($row)
            ->willReturn($expectedDirections);

        $result = $this->service->getHexDirections($row);

        $this->assertEquals($expectedDirections, $result);
    }

    public function testGetNeighborPositions(): void
    {
        $row = 1;
        $col = 1;
        $maxRows = 3;
        $maxCols = 3;

        $adjacentPositions = [
            new Position(0, 1),
            new Position(1, 0),
            new Position(1, 2)
        ];

        $this->hexGridService->expects($this->once())
            ->method('getAdjacentPositions')
            ->with(
                $this->equalTo(new Position($row, $col)),
                $this->equalTo($maxRows),
                $this->equalTo($maxCols)
            )
            ->willReturn($adjacentPositions);

        $result = $this->service->getNeighborPositions($row, $col, $maxRows, $maxCols);

        $expected = [
            ['row' => 0, 'col' => 1],
            ['row' => 1, 'col' => 0],
            ['row' => 1, 'col' => 2]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testCountNeighborsOfType(): void
    {
        $map = [
            [['type' => 'plains'], ['type' => 'forest']],
            [['type' => 'plains'], ['type' => 'water']]
        ];
        $row = 0;
        $col = 0;
        $maxRows = 2;
        $maxCols = 2;
        $terrainType = 'plains';

        $this->hexGridService->expects($this->once())
            ->method('countNeighborsOfType')
            ->with(
                $this->equalTo($map),
                $this->equalTo(new Position($row, $col)),
                $this->equalTo($maxRows),
                $this->equalTo($maxCols),
                $this->equalTo($terrainType)
            )
            ->willReturn(2);

        $result = $this->service->countNeighborsOfType($map, $row, $col, $maxRows, $maxCols, $terrainType);

        $this->assertEquals(2, $result);
    }

    public function testGetNeighborTerrainCounts(): void
    {
        $map = [
            [['type' => 'plains'], ['type' => 'forest']],
            [['type' => 'plains'], ['type' => 'water']]
        ];
        $row = 0;
        $col = 0;
        $maxRows = 2;
        $maxCols = 2;

        $expectedCounts = [
            'plains' => 2,
            'forest' => 1,
            'water' => 1
        ];

        $this->hexGridService->expects($this->once())
            ->method('getNeighborTerrainCounts')
            ->with(
                $this->equalTo($map),
                $this->equalTo(new Position($row, $col)),
                $this->equalTo($maxRows),
                $this->equalTo($maxCols)
            )
            ->willReturn($expectedCounts);

        $result = $this->service->getNeighborTerrainCounts($map, $row, $col, $maxRows, $maxCols);

        $this->assertEquals($expectedCounts, $result);
    }
} 