<?php

namespace Tests\Unit\Application\Player\Service;

use App\Application\Player\Service\MovementCalculationService;
use App\Domain\Player\Service\PlayerTurnDomainService;
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
    private PlayerTurnDomainService|MockObject $turnDomainService;
    private HexGridService|MockObject $hexGridService;

    protected function setUp(): void
    {
        $this->turnDomainService = $this->createMock(PlayerTurnDomainService::class);
        $this->hexGridService = $this->createMock(HexGridService::class);
        $this->service = new MovementCalculationService($this->turnDomainService, $this->hexGridService);
    }

    public function testCalculatePossibleMovesWithNoMovementPoints(): void
    {
        $player = Player::create(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            0 // No movement points
        );

        $mapData = [];
        $mapRows = 10;
        $mapCols = 10;

        $result = $this->service->calculatePossibleMoves($player, $mapData, $mapRows, $mapCols);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testCalculatePossibleMovesWithValidMoves(): void
    {
        $player = Player::create(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $mapData = [
            4 => [5 => ['type' => 'plains']],
            5 => [4 => ['type' => 'plains'], 6 => ['type' => 'plains']],
            6 => [5 => ['type' => 'plains']]
        ];
        $mapRows = 10;
        $mapCols = 10;

        $neighbors = [
            new Position(4, 5),
            new Position(5, 4),
            new Position(5, 6),
            new Position(6, 5)
        ];

        $this->hexGridService->expects($this->once())
            ->method('getAdjacentPositions')
            ->with($player->getPosition(), $mapRows, $mapCols)
            ->willReturn($neighbors);

        // Mock movement validation
        $validationResult = $this->createMock(MovementValidationResult::class);
        $validationResult->method('isValid')->willReturn(true);
        $validationResult->method('getMovementCost')->willReturn(1);
        
        $this->turnDomainService->expects($this->exactly(4))
            ->method('validateMovement')
            ->willReturn($validationResult);

        $result = $this->service->calculatePossibleMoves($player, $mapData, $mapRows, $mapCols);

        $this->assertIsArray($result);
        $this->assertCount(4, $result);
    }

    public function testCalculateDetailedMovementOptions(): void
    {
        $player = Player::create(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $mapData = [
            4 => [5 => ['type' => 'plains']],
            5 => [4 => ['type' => 'forest'], 6 => ['type' => 'mountain']],
            6 => [5 => ['type' => 'water']]
        ];
        $mapRows = 10;
        $mapCols = 10;

        $neighbors = [
            new Position(4, 5), // plains
            new Position(5, 4), // forest
            new Position(5, 6), // mountain
            new Position(6, 5)  // water
        ];

        $this->hexGridService->expects($this->once())
            ->method('getAdjacentPositions')
            ->with($player->getPosition(), $mapRows, $mapCols)
            ->willReturn($neighbors);

        // Mock movement validation
        $validationResult = $this->createMock(MovementValidationResult::class);
        $validationResult->method('isValid')->willReturn(true);
        $validationResult->method('getMovementCost')->willReturn(1);
        
        $this->turnDomainService->expects($this->exactly(4))
            ->method('validateMovement')
            ->willReturn($validationResult);

        $result = $this->service->calculateDetailedMovementOptions($player, $mapData, $mapRows, $mapCols);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalPossibleMoves', $result);
        $this->assertArrayHasKey('movesByCost', $result);
        $this->assertArrayHasKey('hasAffordableMoves', $result);
    }

    public function testCanPlayerMoveToWithInvalidDistance(): void
    {
        $player = Player::create(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $targetPosition = new Position(10, 10); // Far away
        $mapData = [];

        $this->turnDomainService->expects($this->once())
            ->method('arePositionsAdjacent')
            ->with($player->getPosition(), $targetPosition)
            ->willReturn(false);

        $result = $this->service->canPlayerMoveTo($player, $targetPosition, $mapData);

        $this->assertIsArray($result);
        $this->assertFalse($result['canMove']);
    }

    public function testCanPlayerMoveToWithValidMove(): void
    {
        $player = Player::create(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $targetPosition = new Position(5, 6);
        $mapData = [
            5 => [6 => ['type' => 'plains']]
        ];

        $this->turnDomainService->expects($this->once())
            ->method('arePositionsAdjacent')
            ->with($player->getPosition(), $targetPosition)
            ->willReturn(true);

        $validationResult = $this->createMock(MovementValidationResult::class);
        $validationResult->method('isValid')->willReturn(true);
        $validationResult->method('getMovementCost')->willReturn(1);
        
        $this->turnDomainService->expects($this->once())
            ->method('validateMovement')
            ->willReturn($validationResult);

        $result = $this->service->canPlayerMoveTo($player, $targetPosition, $mapData);

        $this->assertIsArray($result);
        $this->assertTrue($result['canMove']);
    }

    public function testCanPlayerMoveToWithInsufficientMovementPoints(): void
    {
        $player = Player::create(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            1 // Only 1 movement point
        );

        $targetPosition = new Position(5, 6);
        $mapData = [
            5 => [6 => ['type' => 'mountain']] // Costs 3 movement points
        ];

        $this->turnDomainService->expects($this->once())
            ->method('arePositionsAdjacent')
            ->with($player->getPosition(), $targetPosition)
            ->willReturn(true);

        $expensiveValidationResult = $this->createMock(MovementValidationResult::class);
        $expensiveValidationResult->method('isValid')->willReturn(true);
        $expensiveValidationResult->method('getMovementCost')->willReturn(3);

        $this->turnDomainService->expects($this->once())
            ->method('validateMovement')
            ->willReturn($expensiveValidationResult);

        $result = $this->service->canPlayerMoveTo($player, $targetPosition, $mapData);

        $this->assertIsArray($result);
        $this->assertFalse($result['canMove']);
    }
} 