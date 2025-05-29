<?php

namespace App\Tests\Unit\Domain\Game\Service;

use App\Domain\Game\Service\MovementDomainService;
use App\Domain\Game\Service\MovementValidationResult;
use App\Domain\Player\ValueObject\Position;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MovementDomainService
 */
class MovementDomainServiceTest extends TestCase
{
    private MovementDomainService $movementService;

    protected function setUp(): void
    {
        $this->movementService = new MovementDomainService();
    }

    public function testValidateMovementReturnsTrueForAdjacentPassableTerrain(): void
    {
        $from = new Position(5, 5);
        $to = new Position(5, 6);
        $terrainData = [
            'type' => 'plains',
            'properties' => ['movementCost' => 1]
        ];

        $result = $this->movementService->validateMovement($from, $to, $terrainData);

        $this->assertTrue($result->isValid());
        $this->assertEquals(1, $result->getMovementCost());
        $this->assertEquals(MovementValidationResult::VALID, $result->getCode());
    }

    public function testValidateMovementReturnsFalseForDistantPosition(): void
    {
        $from = new Position(5, 5);
        $to = new Position(7, 7); // Distance > 1
        $terrainData = [
            'type' => 'plains',
            'properties' => ['movementCost' => 1]
        ];

        $result = $this->movementService->validateMovement($from, $to, $terrainData);

        $this->assertFalse($result->isValid());
        $this->assertEquals('Can only move to adjacent hexes', $result->getReason());
        $this->assertEquals(MovementValidationResult::INVALID_DISTANCE, $result->getCode());
    }

    public function testValidateMovementReturnsFalseForImpassableTerrain(): void
    {
        $from = new Position(5, 5);
        $to = new Position(5, 6);
        $terrainData = [
            'type' => 'water',
            'properties' => ['movementCost' => 0] // Impassable
        ];

        $result = $this->movementService->validateMovement($from, $to, $terrainData);

        $this->assertFalse($result->isValid());
        $this->assertEquals('Cannot move to impassable terrain', $result->getReason());
        $this->assertEquals(MovementValidationResult::IMPASSABLE_TERRAIN, $result->getCode());
    }

    public function testValidateMovementWorksWithDifferentTerrainTypes(): void
    {
        $from = new Position(5, 5);
        $to = new Position(5, 6);

        $testCases = [
            ['type' => 'plains', 'movementCost' => 1],
            ['type' => 'forest', 'movementCost' => 2],
            ['type' => 'mountain', 'movementCost' => 3],
            ['type' => 'desert', 'movementCost' => 2],
            ['type' => 'swamp', 'movementCost' => 3],
        ];

        foreach ($testCases as $testCase) {
            $terrainData = [
                'type' => $testCase['type'],
                'properties' => ['movementCost' => $testCase['movementCost']]
            ];

            $result = $this->movementService->validateMovement($from, $to, $terrainData);

            $this->assertTrue($result->isValid(), "Movement to {$testCase['type']} should be valid");
            $this->assertEquals($testCase['movementCost'], $result->getMovementCost());
        }
    }

    public function testCalculateMovementCostReturnsCorrectCost(): void
    {
        $testCases = [
            ['type' => 'plains', 'expectedCost' => 1],
            ['type' => 'forest', 'expectedCost' => 2],
            ['type' => 'mountain', 'expectedCost' => 3],
            ['type' => 'water', 'expectedCost' => 0],
            ['type' => 'desert', 'expectedCost' => 2],
            ['type' => 'swamp', 'expectedCost' => 3],
        ];

        foreach ($testCases as $testCase) {
            $terrainData = ['type' => $testCase['type']];
            $cost = $this->movementService->calculateMovementCost($terrainData);

            $this->assertEquals(
                $testCase['expectedCost'],
                $cost,
                "Movement cost for {$testCase['type']} should be {$testCase['expectedCost']}"
            );
        }
    }

    public function testArePositionsAdjacentReturnsTrueForAdjacentPositions(): void
    {
        $center = new Position(5, 5);
        
        $adjacentPositions = [
            new Position(4, 5), // North
            new Position(6, 5), // South
            new Position(5, 4), // West
            new Position(5, 6), // East
        ];

        foreach ($adjacentPositions as $adjacent) {
            $this->assertTrue(
                $this->movementService->arePositionsAdjacent($center, $adjacent),
                "Position ({$adjacent->getRow()}, {$adjacent->getCol()}) should be adjacent to center"
            );
        }
    }

    public function testArePositionsAdjacentReturnsFalseForDistantPositions(): void
    {
        $center = new Position(5, 5);
        
        $distantPositions = [
            new Position(3, 3), // Far diagonal
            new Position(7, 7), // Far diagonal
            new Position(5, 8), // Far horizontal
            new Position(8, 5), // Far vertical
        ];

        foreach ($distantPositions as $distant) {
            $this->assertFalse(
                $this->movementService->arePositionsAdjacent($center, $distant),
                "Position ({$distant->getRow()}, {$distant->getCol()}) should not be adjacent to center"
            );
        }
    }

    public function testArePositionsAdjacentReturnsTrueForSamePosition(): void
    {
        $position = new Position(5, 5);

        $this->assertTrue($this->movementService->arePositionsAdjacent($position, $position));
    }

    public function testValidateMovementWorksWithHexagonalNeighbors(): void
    {
        $center = new Position(5, 5);
        $terrainData = [
            'type' => 'plains',
            'properties' => ['movementCost' => 1]
        ];

        // Test hexagonal neighbors (6 directions)
        $hexNeighbors = [
            new Position(4, 4), // Top-left
            new Position(4, 5), // Top-right
            new Position(5, 4), // Left
            new Position(5, 6), // Right
            new Position(6, 4), // Bottom-left
            new Position(6, 5), // Bottom-right
        ];

        foreach ($hexNeighbors as $neighbor) {
            $result = $this->movementService->validateMovement($center, $neighbor, $terrainData);
            
            $this->assertTrue(
                $result->isValid(),
                "Movement to hex neighbor ({$neighbor->getRow()}, {$neighbor->getCol()}) should be valid"
            );
        }
    }

    public function testMovementValidationResultStaticFactoryMethods(): void
    {
        // Test valid result
        $validResult = MovementValidationResult::valid(2);
        $this->assertTrue($validResult->isValid());
        $this->assertEquals(2, $validResult->getMovementCost());
        $this->assertEquals(MovementValidationResult::VALID, $validResult->getCode());

        // Test invalid result
        $invalidResult = MovementValidationResult::invalid('Test reason', 'TEST_CODE');
        $this->assertFalse($invalidResult->isValid());
        $this->assertEquals('Test reason', $invalidResult->getReason());
        $this->assertEquals('TEST_CODE', $invalidResult->getCode());
        $this->assertEquals(0, $invalidResult->getMovementCost());
    }
} 