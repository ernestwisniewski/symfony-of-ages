<?php

namespace Tests\Unit\Application\Player\Service;

use App\Application\Player\Service\MovementCalculationService;
use App\Domain\Player\Service\MovementDomainService;
use App\Domain\Player\Service\MovementValidationResult;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Player\ValueObject\Position;
use App\Domain\Shared\Service\HexGridService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for MovementCalculationService
 */
class MovementCalculationServiceTest extends TestCase
{
    private MovementCalculationService $service;
    private MovementDomainService|MockObject $movementDomainService;
    private HexGridService|MockObject $hexGridService;

    protected function setUp(): void
    {
        $this->movementDomainService = $this->createMock(MovementDomainService::class);
        $this->hexGridService = $this->createMock(HexGridService::class);
        $this->service = new MovementCalculationService($this->movementDomainService, $this->hexGridService);
    }

    public function testCalculatePossibleMovesWithNoMovementPoints(): void
    {
        $player = new Player(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );
        
        // Set movement points to 0
        $player->moveTo(new Position(5, 6), 3);
        
        $mapData = [
            [['type' => 'plains', 'name' => 'Plains']]
        ];

        $result = $this->service->calculatePossibleMoves($player, $mapData, 10, 10);

        $this->assertEmpty($result);
    }

    public function testCalculatePossibleMovesWithValidMoves(): void
    {
        $player = new Player(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $mapData = [
            5 => [
                5 => ['type' => 'plains', 'name' => 'Plains'],
                6 => ['type' => 'forest', 'name' => 'Forest']
            ],
            6 => [
                5 => ['type' => 'mountain', 'name' => 'Mountain']
            ]
        ];

        $adjacentPositions = [
            new Position(5, 6),
            new Position(6, 5)
        ];

        $this->hexGridService->expects($this->once())
            ->method('getAdjacentPositions')
            ->willReturn($adjacentPositions);

        // Mock validation results for each position
        $validationResult1 = $this->createMock(MovementValidationResult::class);
        $validationResult1->method('isValid')->willReturn(true);
        $validationResult1->method('getMovementCost')->willReturn(2);

        $validationResult2 = $this->createMock(MovementValidationResult::class);
        $validationResult2->method('isValid')->willReturn(true);
        $validationResult2->method('getMovementCost')->willReturn(3);

        $this->movementDomainService->expects($this->exactly(2))
            ->method('validateMovement')
            ->willReturnOnConsecutiveCalls($validationResult1, $validationResult2);

        $result = $this->service->calculatePossibleMoves($player, $mapData, 10, 10);

        $this->assertCount(2, $result);
        $this->assertEquals(2, $result[0]['movementCost']);
        $this->assertEquals(3, $result[1]['movementCost']);
        $this->assertTrue($result[0]['canAfford']);
        $this->assertTrue($result[1]['canAfford']);
    }

    public function testCalculateDetailedMovementOptions(): void
    {
        $player = new Player(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $mapData = [
            5 => [
                5 => ['type' => 'plains', 'name' => 'Plains'],
                6 => ['type' => 'forest', 'name' => 'Forest']
            ]
        ];

        $adjacentPositions = [new Position(5, 6)];

        $this->hexGridService->method('getAdjacentPositions')
            ->willReturn($adjacentPositions);

        $validationResult = $this->createMock(MovementValidationResult::class);
        $validationResult->method('isValid')->willReturn(true);
        $validationResult->method('getMovementCost')->willReturn(2);

        $this->movementDomainService->method('validateMovement')
            ->willReturn($validationResult);

        $result = $this->service->calculateDetailedMovementOptions($player, $mapData, 10, 10);

        $this->assertArrayHasKey('totalPossibleMoves', $result);
        $this->assertArrayHasKey('currentMovementPoints', $result);
        $this->assertArrayHasKey('maxMovementPoints', $result);
        $this->assertArrayHasKey('movesByCost', $result);
        $this->assertArrayHasKey('availableTerrainTypes', $result);
        $this->assertArrayHasKey('allMoves', $result);

        $this->assertEquals(1, $result['totalPossibleMoves']);
        $this->assertEquals(3, $result['currentMovementPoints']);
        $this->assertEquals(3, $result['maxMovementPoints']);
    }

    public function testCanPlayerMoveToWithInvalidDistance(): void
    {
        $player = new Player(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $targetPosition = new Position(7, 7); // Too far
        $mapData = [
            7 => [7 => ['type' => 'plains', 'name' => 'Plains']]
        ];

        $this->movementDomainService->expects($this->once())
            ->method('arePositionsAdjacent')
            ->willReturn(false);

        $result = $this->service->canPlayerMoveTo($player, $targetPosition, $mapData);

        $this->assertFalse($result['canMove']);
        $this->assertEquals('Position is not adjacent', $result['reason']);
        $this->assertEquals('NOT_ADJACENT', $result['code']);
    }

    public function testCanPlayerMoveToWithValidMove(): void
    {
        $player = new Player(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $targetPosition = new Position(5, 6);
        $mapData = [
            5 => [6 => ['type' => 'plains', 'name' => 'Plains']]
        ];

        $this->movementDomainService->expects($this->once())
            ->method('arePositionsAdjacent')
            ->willReturn(true);

        $validationResult = $this->createMock(MovementValidationResult::class);
        $validationResult->method('isValid')->willReturn(true);
        $validationResult->method('getMovementCost')->willReturn(1);

        $this->movementDomainService->expects($this->once())
            ->method('validateMovement')
            ->willReturn($validationResult);

        $result = $this->service->canPlayerMoveTo($player, $targetPosition, $mapData);

        $this->assertTrue($result['canMove']);
        $this->assertEquals(1, $result['movementCost']);
        $this->assertEquals(2, $result['remainingMovementAfter']);
        $this->assertEquals('Move is valid', $result['reason']);
        $this->assertEquals('VALID', $result['code']);
    }

    public function testCanPlayerMoveToWithInsufficientMovementPoints(): void
    {
        $player = new Player(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );
        
        // Use up 2 movement points
        $player->moveTo(new Position(5, 6), 2);

        $targetPosition = new Position(5, 4);
        $mapData = [
            5 => [4 => ['type' => 'mountain', 'name' => 'Mountain']]
        ];

        $this->movementDomainService->expects($this->once())
            ->method('arePositionsAdjacent')
            ->willReturn(true);

        $validationResult = $this->createMock(MovementValidationResult::class);
        $validationResult->method('isValid')->willReturn(true);
        $validationResult->method('getMovementCost')->willReturn(3); // More than remaining

        $this->movementDomainService->expects($this->once())
            ->method('validateMovement')
            ->willReturn($validationResult);

        $result = $this->service->canPlayerMoveTo($player, $targetPosition, $mapData);

        $this->assertFalse($result['canMove']);
        $this->assertEquals(3, $result['movementCost']);
        $this->assertEquals(0, $result['remainingMovementAfter']);
        $this->assertEquals('Insufficient movement points', $result['reason']);
        $this->assertEquals('INSUFFICIENT_MOVEMENT_POINTS', $result['code']);
    }
} 