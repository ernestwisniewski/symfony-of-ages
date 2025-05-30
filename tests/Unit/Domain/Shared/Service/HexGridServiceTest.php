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

    #[DataProvider('hexDirectionsProvider')]
    public function testGetHexDirections(int $row, array $expectedDirections): void
    {
        $directions = $this->hexGridService->getHexDirections($row);
        
        $this->assertEquals($expectedDirections, $directions);
    }

    public function testGetAdjacentPositionsEvenRow(): void
    {
        $position = new Position(4, 5); // Even row
        $adjacentPositions = $this->hexGridService->getAdjacentPositions($position, 10, 10);
        
        $this->assertCount(6, $adjacentPositions);
        
        $expectedPositionStrings = [
            '(3, 4)', '(3, 5)', // Top neighbors
            '(4, 4)', '(4, 6)', // Side neighbors  
            '(5, 4)', '(5, 5)'  // Bottom neighbors
        ];
        
        $actualPositionStrings = array_map(fn($pos) => $pos->__toString(), $adjacentPositions);
        
        foreach ($expectedPositionStrings as $expectedPosString) {
            $this->assertContains($expectedPosString, $actualPositionStrings);
        }
    }

    public function testGetAdjacentPositionsOddRow(): void
    {
        $position = new Position(5, 5); // Odd row
        $adjacentPositions = $this->hexGridService->getAdjacentPositions($position, 10, 10);
        
        $this->assertCount(6, $adjacentPositions);
        
        $expectedPositionStrings = [
            '(4, 5)', '(4, 6)', // Top neighbors
            '(5, 4)', '(5, 6)', // Side neighbors
            '(6, 5)', '(6, 6)'  // Bottom neighbors
        ];
        
        $actualPositionStrings = array_map(fn($pos) => $pos->__toString(), $adjacentPositions);
        
        foreach ($expectedPositionStrings as $expectedPosString) {
            $this->assertContains($expectedPosString, $actualPositionStrings);
        }
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
            'Far away' => [new Position(0, 0), new Position(10, 10), false],
        ];
    }
} 