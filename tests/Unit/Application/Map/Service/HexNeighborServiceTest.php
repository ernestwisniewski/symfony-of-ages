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
            [
                ['type' => 'plains', 'coordinates' => ['row' => 0, 'col' => 0]], 
                ['type' => 'forest', 'coordinates' => ['row' => 0, 'col' => 1]]
            ],
            [
                ['type' => 'mountain', 'coordinates' => ['row' => 1, 'col' => 0]], 
                ['type' => 'water', 'coordinates' => ['row' => 1, 'col' => 1]]
            ]
        ];
        $row = 0;
        $col = 0;
        $maxRows = 2;
        $maxCols = 2;

        $adjacentPositions = [
            new Position(0, 1),
            new Position(1, 0)
        ];

        $this->hexGridService->expects($this->once())
            ->method('getAdjacentPositions')
            ->with(
                $this->equalTo(new Position($row, $col)),
                $this->equalTo($maxRows),
                $this->equalTo($maxCols)
            )
            ->willReturn($adjacentPositions);

        $result = $this->service->getNeighbors($map, $row, $col, $maxRows, $maxCols);

        $expectedNeighbors = [
            ['type' => 'forest', 'coordinates' => ['row' => 0, 'col' => 1]],
            ['type' => 'mountain', 'coordinates' => ['row' => 1, 'col' => 0]]
        ];

        $this->assertEquals($expectedNeighbors, $result);
    }

    public function testGetHexDirections(): void
    {
        $row = 0;
        $expectedDirections = [[-1, -1], [-1, 0], [0, -1], [0, 1], [1, -1], [1, 0]];

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
            [
                ['type' => 'plains', 'coordinates' => ['row' => 0, 'col' => 0]], 
                ['type' => 'forest', 'coordinates' => ['row' => 0, 'col' => 1]]
            ],
            [
                ['type' => 'plains', 'coordinates' => ['row' => 1, 'col' => 0]], 
                ['type' => 'water', 'coordinates' => ['row' => 1, 'col' => 1]]
            ]
        ];
        $row = 0;
        $col = 0;
        $maxRows = 2;
        $maxCols = 2;
        $terrainType = 'plains';

        $adjacentPositions = [
            new Position(0, 1), // forest
            new Position(1, 0)  // plains
        ];

        $this->hexGridService->expects($this->once())
            ->method('getAdjacentPositions')
            ->with(
                $this->equalTo(new Position($row, $col)),
                $this->equalTo($maxRows),
                $this->equalTo($maxCols)
            )
            ->willReturn($adjacentPositions);

        $result = $this->service->countNeighborsOfType($map, $row, $col, $maxRows, $maxCols, $terrainType);

        $this->assertEquals(1, $result); // Only one plains neighbor
    }

    public function testGetNeighborTerrainCounts(): void
    {
        $map = [
            [
                ['type' => 'plains', 'coordinates' => ['row' => 0, 'col' => 0]], 
                ['type' => 'forest', 'coordinates' => ['row' => 0, 'col' => 1]]
            ],
            [
                ['type' => 'plains', 'coordinates' => ['row' => 1, 'col' => 0]], 
                ['type' => 'water', 'coordinates' => ['row' => 1, 'col' => 1]]
            ]
        ];
        $row = 0;
        $col = 0;
        $maxRows = 2;
        $maxCols = 2;

        $adjacentPositions = [
            new Position(0, 1), // forest
            new Position(1, 0)  // plains
        ];

        $this->hexGridService->expects($this->once())
            ->method('getAdjacentPositions')
            ->with(
                $this->equalTo(new Position($row, $col)),
                $this->equalTo($maxRows),
                $this->equalTo($maxCols)
            )
            ->willReturn($adjacentPositions);

        $result = $this->service->getNeighborTerrainCounts($map, $row, $col, $maxRows, $maxCols);

        $expectedCounts = [
            'forest' => 1,
            'plains' => 1
        ];

        $this->assertEquals($expectedCounts, $result);
    }
} 