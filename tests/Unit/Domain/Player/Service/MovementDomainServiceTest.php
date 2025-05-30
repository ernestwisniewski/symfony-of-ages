<?php

namespace Tests\Unit\Domain\Player\Service;

use App\Domain\Player\Service\MovementDomainService;
use App\Domain\Player\Service\MovementValidationResult;
use App\Domain\Player\ValueObject\Position;
use App\Domain\Shared\Service\HexGridService;
use App\Domain\Map\Enum\TerrainType;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for MovementDomainService
 */
class MovementDomainServiceTest extends TestCase
{
    private MovementDomainService $service;
    private HexGridService|MockObject $hexGridService;

    protected function setUp(): void
    {
        $this->hexGridService = $this->createMock(HexGridService::class);
        $this->service = new MovementDomainService($this->hexGridService);
    }

    public function testValidateMovementWithPassableTerrain(): void
    {
        $from = new Position(5, 5);
        $to = new Position(5, 6);
        $terrainData = [
            'type' => 'plains',
            'properties' => ['movementCost' => 1]
        ];

        $this->hexGridService->expects($this->once())
            ->method('arePositionsAdjacent')
            ->with($from, $to)
            ->willReturn(true);

        $result = $this->service->validateMovement($from, $to, $terrainData);

        $this->assertTrue($result->isValid());
        $this->assertEquals(1, $result->getMovementCost());
    }

    public function testValidateMovementWithImpassableTerrain(): void
    {
        $from = new Position(5, 5);
        $to = new Position(5, 6);
        $terrainData = [
            'type' => 'water',
            'properties' => ['movementCost' => 0]
        ];

        // No expectation for arePositionsAdjacent since validation fails early on terrain check

        $result = $this->service->validateMovement($from, $to, $terrainData);

        $this->assertFalse($result->isValid());
        $this->assertEquals('Cannot move to impassable terrain: Water', $result->getReason());
        $this->assertEquals('IMPASSABLE_TERRAIN', $result->getCode());
    }

    public function testValidateMovementWithNonAdjacentPositions(): void
    {
        $from = new Position(5, 5);
        $to = new Position(7, 7);
        $terrainData = [
            'type' => 'plains',
            'properties' => ['movementCost' => 1]
        ];

        $this->hexGridService->expects($this->once())
            ->method('arePositionsAdjacent')
            ->with($from, $to)
            ->willReturn(false);

        $result = $this->service->validateMovement($from, $to, $terrainData);

        $this->assertFalse($result->isValid());
        $this->assertEquals('Target position is not adjacent to current position', $result->getReason());
        $this->assertEquals('NOT_ADJACENT', $result->getCode());
    }

    public function testCalculateMovementCostFromTerrainData(): void
    {
        $terrainData = [
            'type' => 'mountain',
            'properties' => ['movementCost' => 3]
        ];

        $result = $this->service->calculateMovementCost($terrainData);

        $this->assertEquals(3, $result);
    }

    public function testCalculateMovementCostWithMissingProperties(): void
    {
        $terrainData = [
            'type' => 'forest'
            // Missing properties - should use TerrainType defaults
        ];

        $result = $this->service->calculateMovementCost($terrainData);

        $this->assertEquals(2, $result); // Forest has movement cost 2 according to TerrainType
    }

    public function testArePositionsAdjacent(): void
    {
        $from = new Position(5, 5);
        $to = new Position(5, 6);

        $this->hexGridService->expects($this->once())
            ->method('arePositionsAdjacent')
            ->with($from, $to)
            ->willReturn(true);

        $result = $this->service->arePositionsAdjacent($from, $to);

        $this->assertTrue($result);
    }

    public function testArePositionsNotAdjacent(): void
    {
        $from = new Position(5, 5);
        $to = new Position(7, 7);

        $this->hexGridService->expects($this->once())
            ->method('arePositionsAdjacent')
            ->with($from, $to)
            ->willReturn(false);

        $result = $this->service->arePositionsAdjacent($from, $to);

        $this->assertFalse($result);
    }
}

/**
 * Unit tests for MovementValidationResult
 */
class MovementValidationResultTest extends TestCase
{
    public function testValidResult(): void
    {
        $result = MovementValidationResult::valid(2);

        $this->assertTrue($result->isValid());
        $this->assertEquals(2, $result->getMovementCost());
        $this->assertEquals('Movement is valid', $result->getReason());
        $this->assertEquals('valid', $result->getCode());
    }

    public function testInvalidResult(): void
    {
        $result = MovementValidationResult::invalid('Terrain is impassable', 'IMPASSABLE');

        $this->assertFalse($result->isValid());
        $this->assertEquals(0, $result->getMovementCost());
        $this->assertEquals('Terrain is impassable', $result->getReason());
        $this->assertEquals('IMPASSABLE', $result->getCode());
    }
} 