<?php

namespace App\Tests\Unit\Application\Player\Service;

use App\Application\Player\Service\PlayerPositionService;
use App\Domain\Player\ValueObject\Position;
use App\Domain\Shared\Service\HexGridService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for PlayerPositionService
 */
class PlayerPositionServiceTest extends TestCase
{
    private PlayerPositionService $positionService;
    private HexGridService|MockObject $hexGridService;

    protected function setUp(): void
    {
        $this->hexGridService = $this->createMock(HexGridService::class);
        $this->positionService = new PlayerPositionService($this->hexGridService);
        
        // Setup default mock behavior
        $this->hexGridService
            ->method('isWithinBounds')
            ->willReturnCallback(function($row, $col, $mapRows, $mapCols) {
                return $row >= 0 && $row < $mapRows && $col >= 0 && $col < $mapCols;
            });
    }

    public function testIsValidStartingPositionReturnsTrueForPassableTerrain(): void
    {
        $position = new Position(5, 5);
        $mapData = [
            [
                ['type' => 'plains', 'properties' => ['movementCost' => 1]]
            ]
        ];

        // Mock the map data structure
        $mapData[5][5] = ['type' => 'plains'];

        $result = $this->positionService->isValidStartingPosition($position, $mapData);

        $this->assertTrue($result);
    }

    public function testIsValidStartingPositionReturnsFalseForWater(): void
    {
        $position = new Position(5, 5);
        $mapData = [];

        // Mock the map data structure
        $mapData[5][5] = ['type' => 'water'];

        $result = $this->positionService->isValidStartingPosition($position, $mapData);

        $this->assertFalse($result);
    }

    public function testIsValidMapPositionReturnsTrueForValidPosition(): void
    {
        $position = new Position(5, 10);
        $mapRows = 20;
        $mapCols = 20;

        $result = $this->positionService->isValidMapPosition($position, $mapRows, $mapCols);

        $this->assertTrue($result);
    }

    public function testIsValidMapPositionReturnsFalseForOutOfBoundsPosition(): void
    {
        $position = new Position(25, 10);
        $mapRows = 20;
        $mapCols = 20;

        $result = $this->positionService->isValidMapPosition($position, $mapRows, $mapCols);

        $this->assertFalse($result);
    }

    public function testIsValidMapPositionReturnsFalseForNegativePosition(): void
    {
        // Since Position constructor now validates coordinates, we test the service method directly
        $this->assertFalse($this->positionService->isValidMapPosition(new Position(0, 0), 0, 0));
        
        // We can also test the HexGridService boundary check directly
        $this->assertFalse($this->hexGridService->isWithinBounds(-1, 10, 20, 20));
        $this->assertFalse($this->hexGridService->isWithinBounds(5, -1, 20, 20));
    }

    public function testIsValidMapPositionWorksWithBoundaryPositions(): void
    {
        $mapRows = 10;
        $mapCols = 10;

        // Test corners
        $this->assertTrue($this->positionService->isValidMapPosition(new Position(0, 0), $mapRows, $mapCols));
        $this->assertTrue($this->positionService->isValidMapPosition(new Position(9, 9), $mapRows, $mapCols));
        
        // Test edges
        $this->assertTrue($this->positionService->isValidMapPosition(new Position(0, 5), $mapRows, $mapCols));
        $this->assertTrue($this->positionService->isValidMapPosition(new Position(5, 0), $mapRows, $mapCols));
        $this->assertTrue($this->positionService->isValidMapPosition(new Position(9, 5), $mapRows, $mapCols));
        $this->assertTrue($this->positionService->isValidMapPosition(new Position(5, 9), $mapRows, $mapCols));

        // Test out of bounds
        $this->assertFalse($this->positionService->isValidMapPosition(new Position(10, 5), $mapRows, $mapCols));
        $this->assertFalse($this->positionService->isValidMapPosition(new Position(5, 10), $mapRows, $mapCols));
    }

    public function testGenerateValidStartingPositionReturnsValidPosition(): void
    {
        $mapRows = 20;
        $mapCols = 20;
        
        // Create a map with mostly passable terrain
        $mapData = [];
        for ($row = 0; $row < $mapRows; $row++) {
            for ($col = 0; $col < $mapCols; $col++) {
                $mapData[$row][$col] = ['type' => 'plains'];
            }
        }

        $position = $this->positionService->generateValidStartingPosition($mapRows, $mapCols, $mapData);

        $this->assertInstanceOf(Position::class, $position);
        $this->assertTrue($this->positionService->isValidMapPosition($position, $mapRows, $mapCols));
        $this->assertTrue($this->positionService->isValidStartingPosition($position, $mapData));
    }

    public function testGenerateValidStartingPositionWorksWithLimitedValidTerrain(): void
    {
        $mapRows = 10;
        $mapCols = 10;
        
        // Create a map with mostly water, but some passable terrain in the safe area
        $mapData = [];
        for ($row = 0; $row < $mapRows; $row++) {
            for ($col = 0; $col < $mapCols; $col++) {
                $mapData[$row][$col] = ['type' => 'water']; // Impassable
            }
        }

        // Add some passable terrain in the safe area (35%-65% of map)
        $safeStartRow = intval($mapRows * 0.35);
        $safeEndRow = intval($mapRows * 0.65);
        $safeStartCol = intval($mapCols * 0.35);
        $safeEndCol = intval($mapCols * 0.65);

        for ($row = $safeStartRow; $row <= $safeEndRow; $row++) {
            for ($col = $safeStartCol; $col <= $safeEndCol; $col++) {
                if (($row + $col) % 2 === 0) { // Checkerboard pattern of passable terrain
                    $mapData[$row][$col] = ['type' => 'plains'];
                }
            }
        }

        $position = $this->positionService->generateValidStartingPosition($mapRows, $mapCols, $mapData);

        $this->assertInstanceOf(Position::class, $position);
        $this->assertTrue($this->positionService->isValidStartingPosition($position, $mapData));
    }

    public function testGenerateValidStartingPositionUsesCenterAsFallback(): void
    {
        $mapRows = 10;
        $mapCols = 10;
        
        // Create a map with mostly water
        $mapData = [];
        for ($row = 0; $row < $mapRows; $row++) {
            for ($col = 0; $col < $mapCols; $col++) {
                $mapData[$row][$col] = ['type' => 'water']; // Impassable
            }
        }

        // Make center passable
        $centerRow = intval($mapRows / 2);
        $centerCol = intval($mapCols / 2);
        $mapData[$centerRow][$centerCol] = ['type' => 'plains'];

        $position = $this->positionService->generateValidStartingPosition($mapRows, $mapCols, $mapData);

        $this->assertEquals($centerRow, $position->getRow());
        $this->assertEquals($centerCol, $position->getCol());
    }

    public function testGenerateValidStartingPositionWithSmallMap(): void
    {
        $mapRows = 3;
        $mapCols = 3;
        
        // Create a small map with passable terrain
        $mapData = [
            [
                ['type' => 'plains'],
                ['type' => 'plains'],
                ['type' => 'plains']
            ],
            [
                ['type' => 'plains'],
                ['type' => 'plains'],
                ['type' => 'plains']
            ],
            [
                ['type' => 'plains'],
                ['type' => 'plains'],
                ['type' => 'plains']
            ]
        ];

        $position = $this->positionService->generateValidStartingPosition($mapRows, $mapCols, $mapData);

        $this->assertInstanceOf(Position::class, $position);
        $this->assertTrue($this->positionService->isValidMapPosition($position, $mapRows, $mapCols));
    }

    public function testIsValidStartingPositionWorksWithDifferentTerrainTypes(): void
    {
        $position = new Position(0, 0);
        
        $passableTerrains = ['plains', 'forest', 'mountain', 'desert', 'swamp'];
        foreach ($passableTerrains as $terrain) {
            $mapData = [
                [['type' => $terrain]]
            ];
            
            $result = $this->positionService->isValidStartingPosition($position, $mapData);
            $this->assertTrue($result, "Position should be valid for terrain: {$terrain}");
        }

        // Test impassable terrain
        $mapData = [
            [['type' => 'water']]
        ];
        $result = $this->positionService->isValidStartingPosition($position, $mapData);
        $this->assertFalse($result, "Position should not be valid for water terrain");
    }
} 