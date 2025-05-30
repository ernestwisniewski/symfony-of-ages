<?php

namespace Tests\Unit\Domain\Shared\Service;

use App\Domain\Player\ValueObject\Position;
use App\Domain\Shared\Service\HexGridService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for HexGridService
 */
class HexGridServiceTest extends TestCase
{
    private HexGridService $hexGridService;

    protected function setUp(): void
    {
        $this->hexGridService = new HexGridService();
    }

    public function testGetAdjacentPositions(): void
    {
        $position = new Position(5, 5);
        $adjacentPositions = $this->hexGridService->getAdjacentPositions($position, 10, 10);

        $this->assertCount(6, $adjacentPositions); // Hexagonal grid has 6 neighbors
        
        foreach ($adjacentPositions as $adjacentPosition) {
            $this->assertInstanceOf(Position::class, $adjacentPosition);
            $this->assertTrue($adjacentPosition->isValidForMap(10, 10));
        }
    }

    public function testGetAdjacentPositionsAtMapEdge(): void
    {
        $position = new Position(0, 0); // Corner position
        $adjacentPositions = $this->hexGridService->getAdjacentPositions($position, 10, 10);

        // Should have fewer neighbors due to map boundaries
        $this->assertLessThan(6, count($adjacentPositions));
        
        foreach ($adjacentPositions as $adjacentPosition) {
            $this->assertInstanceOf(Position::class, $adjacentPosition);
            $this->assertTrue($adjacentPosition->isValidForMap(10, 10));
        }
    }

    #[DataProvider('hexDirectionsProvider')]
    public function testGetHexDirections(int $row, array $expectedDirections): void
    {
        $directions = $this->hexGridService->getHexDirections($row);
        
        $this->assertCount(6, $directions);
        $this->assertEquals($expectedDirections, $directions);
    }

    public function testGetNeighborTiles(): void
    {
        $map = [
            [
                ['type' => 'plains', 'name' => 'Plains'],
                ['type' => 'forest', 'name' => 'Forest'],
                ['type' => 'mountain', 'name' => 'Mountain']
            ],
            [
                ['type' => 'water', 'name' => 'Water'],
                ['type' => 'desert', 'name' => 'Desert'],
                ['type' => 'swamp', 'name' => 'Swamp']
            ]
        ];

        $position = new Position(0, 1); // Forest position
        $neighbors = $this->hexGridService->getNeighborTiles($map, $position, 2, 3);

        $this->assertIsArray($neighbors);
        $this->assertNotEmpty($neighbors);

        foreach ($neighbors as $neighbor) {
            $this->assertArrayHasKey('type', $neighbor);
            $this->assertArrayHasKey('name', $neighbor);
        }
    }

    public function testCountNeighborsOfType(): void
    {
        $map = [
            [
                ['type' => 'plains'],
                ['type' => 'forest'],
                ['type' => 'forest']
            ],
            [
                ['type' => 'plains'],
                ['type' => 'forest'],
                ['type' => 'plains']
            ],
            [
                ['type' => 'forest'],
                ['type' => 'plains'],
                ['type' => 'mountain']
            ]
        ];

        $position = new Position(1, 1); // Center position
        $forestCount = $this->hexGridService->countNeighborsOfType($map, $position, 3, 3, 'forest');
        $plainsCount = $this->hexGridService->countNeighborsOfType($map, $position, 3, 3, 'plains');

        $this->assertGreaterThan(0, $forestCount);
        $this->assertGreaterThan(0, $plainsCount);
        $this->assertLessThanOrEqual(6, $forestCount + $plainsCount);
    }

    public function testGetNeighborTerrainCounts(): void
    {
        $map = [
            [
                ['type' => 'plains'],
                ['type' => 'forest'],
                ['type' => 'forest']
            ],
            [
                ['type' => 'plains'],
                ['type' => 'forest'],
                ['type' => 'plains']
            ]
        ];

        $position = new Position(0, 1); // Forest position
        $terrainCounts = $this->hexGridService->getNeighborTerrainCounts($map, $position, 2, 3);

        $this->assertIsArray($terrainCounts);
        $this->assertArrayHasKey('plains', $terrainCounts);
        $this->assertArrayHasKey('forest', $terrainCounts);
        
        $totalNeighbors = array_sum($terrainCounts);
        $this->assertLessThanOrEqual(6, $totalNeighbors);
    }

    #[DataProvider('mapBoundsProvider')]
    public function testIsWithinBounds(int $row, int $col, int $mapRows, int $mapCols, bool $expected): void
    {
        $result = $this->hexGridService->isWithinBounds($row, $col, $mapRows, $mapCols);
        
        $this->assertEquals($expected, $result);
    }

    #[DataProvider('distanceProvider')]
    public function testCalculateDistance(Position $from, Position $to, int $expectedDistance): void
    {
        $distance = $this->hexGridService->calculateDistance($from, $to);
        
        $this->assertEquals($expectedDistance, $distance);
    }

    #[DataProvider('adjacencyProvider')]
    public function testArePositionsAdjacent(Position $from, Position $to, bool $expected): void
    {
        $result = $this->hexGridService->arePositionsAdjacent($from, $to);
        
        $this->assertEquals($expected, $result);
    }

    public function testGetAdjacentPositionsConsistency(): void
    {
        $position = new Position(5, 5);
        $adjacentPositions = $this->hexGridService->getAdjacentPositions($position, 10, 10);

        // All adjacent positions should be considered adjacent by the adjacency check
        foreach ($adjacentPositions as $adjacentPosition) {
            $this->assertTrue($this->hexGridService->arePositionsAdjacent($position, $adjacentPosition));
        }
        
        // Verify we have the expected number of neighbors
        $this->assertCount(6, $adjacentPositions);
    }

    public function testHexDirectionsForEvenAndOddRows(): void
    {
        $evenRowDirections = $this->hexGridService->getHexDirections(4); // Even row
        $oddRowDirections = $this->hexGridService->getHexDirections(5);   // Odd row

        // Both should have 6 directions
        $this->assertCount(6, $evenRowDirections);
        $this->assertCount(6, $oddRowDirections);

        // Directions should be different for even and odd rows
        $this->assertNotEquals($evenRowDirections, $oddRowDirections);

        // Each direction should be a 2-element array [row_offset, col_offset]
        foreach ($evenRowDirections as $direction) {
            $this->assertCount(2, $direction);
            $this->assertIsInt($direction[0]);
            $this->assertIsInt($direction[1]);
        }
    }

    public function testEmptyMapHandling(): void
    {
        $emptyMap = [];
        $position = new Position(0, 0);

        $neighbors = $this->hexGridService->getNeighborTiles($emptyMap, $position, 0, 0);
        $this->assertEmpty($neighbors);

        $terrainCounts = $this->hexGridService->getNeighborTerrainCounts($emptyMap, $position, 0, 0);
        $this->assertEmpty($terrainCounts);

        $count = $this->hexGridService->countNeighborsOfType($emptyMap, $position, 0, 0, 'forest');
        $this->assertEquals(0, $count);
    }

    public static function hexDirectionsProvider(): array
    {
        return [
            'Even row' => [
                4,
                [
                    [-1, -1], [-1, 0], // Top-left, Top-right
                    [0, -1], [0, 1],   // Left, Right
                    [1, -1], [1, 0]    // Bottom-left, Bottom-right
                ]
            ],
            'Odd row' => [
                5,
                [
                    [-1, 0], [-1, 1],  // Top-left, Top-right
                    [0, -1], [0, 1],    // Left, Right
                    [1, 0], [1, 1]      // Bottom-left, Bottom-right
                ]
            ]
        ];
    }

    public static function mapBoundsProvider(): array
    {
        return [
            'Valid position' => [5, 5, 10, 10, true],
            'Top-left corner' => [0, 0, 10, 10, true],
            'Bottom-right corner' => [9, 9, 10, 10, true],
            'Row out of bounds (negative)' => [-1, 5, 10, 10, false],
            'Col out of bounds (negative)' => [5, -1, 10, 10, false],
            'Row out of bounds (too high)' => [10, 5, 10, 10, false],
            'Col out of bounds (too high)' => [5, 10, 10, 10, false],
        ];
    }

    public static function distanceProvider(): array
    {
        return [
            'Same position' => [new Position(5, 5), new Position(5, 5), 0],
            'Adjacent horizontal' => [new Position(5, 5), new Position(5, 6), 1],
            'Adjacent vertical even' => [new Position(4, 5), new Position(3, 5), 1],
            'Adjacent diagonal even' => [new Position(4, 5), new Position(3, 4), 2],
            'Two steps away' => [new Position(5, 5), new Position(5, 7), 2],
            'Complex distance' => [new Position(0, 0), new Position(3, 2), 3],
        ];
    }

    public static function adjacencyProvider(): array
    {
        return [
            'Same position' => [new Position(5, 5), new Position(5, 5), true],
            'Adjacent right' => [new Position(5, 5), new Position(5, 6), true],
            'Adjacent left' => [new Position(5, 5), new Position(5, 4), true],
            'Adjacent top-right even' => [new Position(4, 5), new Position(3, 5), true],
            'Adjacent bottom-left even' => [new Position(4, 5), new Position(5, 4), true],
            'Two steps away' => [new Position(5, 5), new Position(5, 7), false],
            'Diagonal non-adjacent' => [new Position(5, 5), new Position(6, 6), true],
        ];
    }
} 