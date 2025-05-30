<?php

namespace App\Tests\Unit\Application\Player\Service;

use App\Application\Player\Service\PlayerMovementService;
use App\Application\Player\Service\PlayerPositionService;
use App\Domain\Player\Service\PlayerTurnDomainService;
use App\Domain\Player\Service\MovementExecutionResult;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\ValueObject\Position;
use App\Domain\Player\Repository\PlayerRepositoryInterface;
use App\Domain\Game\Exception\MovementNotAllowedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use App\Domain\Player\ValueObject\MovementPoints;
use App\Domain\Shared\Service\HexGridService;

/**
 * Unit tests for PlayerMovementService
 */
class PlayerMovementServiceTest extends TestCase
{
    private PlayerMovementService $movementService;
    private PlayerPositionService|MockObject $positionService;
    private PlayerTurnDomainService|MockObject $turnDomainService;
    private HexGridService|MockObject $hexGridService;

    protected function setUp(): void
    {
        $this->positionService = $this->createMock(PlayerPositionService::class);
        $this->turnDomainService = $this->createMock(PlayerTurnDomainService::class);
        $this->hexGridService = $this->createMock(HexGridService::class);
        
        $this->movementService = new PlayerMovementService(
            $this->positionService,
            $this->turnDomainService,
            $this->hexGridService
        );
    }

    public function testMovePlayerSuccessfully(): void
    {
        $player = Player::create(
            new PlayerId('player_123'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $targetPosition = new Position(5, 6);
        $mapData = [];
        
        // Initialize map structure
        for ($i = 0; $i < 7; $i++) {
            for ($j = 0; $j < 7; $j++) {
                $mapData[$i][$j] = ['type' => 'plains', 'name' => 'Plains'];
            }
        }
        
        // Set target terrain
        $mapData[5][6] = ['type' => 'plains', 'name' => 'Plains'];

        // Mock position validation
        $this->positionService
            ->expects($this->once())
            ->method('isValidMapPosition')
            ->with($targetPosition, 7, 7)
            ->willReturn(true);

        // Mock movement execution
        $executionResult = MovementExecutionResult::success(2, 1);
        $this->turnDomainService
            ->expects($this->once())
            ->method('executeMovement')
            ->with($player, $targetPosition, ['type' => 'plains', 'name' => 'Plains'])
            ->willReturn($executionResult);

        $result = $this->movementService->movePlayer($player, $targetPosition, $mapData);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['movementCost']);
        $this->assertEquals(1, $result['remainingMovement']);
    }

    public function testMovePlayerToInvalidPosition(): void
    {
        $player = Player::create(
            new PlayerId('player_123'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $targetPosition = new Position(25, 25); // Out of bounds
        $mapData = [['type' => 'plains']];

        // Mock position validation to return false
        $this->positionService
            ->expects($this->once())
            ->method('isValidMapPosition')
            ->with($targetPosition, 1, 1)
            ->willReturn(false);

        $result = $this->movementService->movePlayer($player, $targetPosition, $mapData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Target position is outside map bounds', $result['message']);
    }

    public function testMovePlayerToImpassableTerrain(): void
    {
        $player = Player::create(
            new PlayerId('player_123'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $targetPosition = new Position(5, 6);
        $mapData = [];
        
        // Initialize map structure  
        for ($i = 0; $i < 7; $i++) {
            for ($j = 0; $j < 7; $j++) {
                $mapData[$i][$j] = ['type' => 'plains', 'name' => 'Plains'];
            }
        }
        
        // Set target terrain to water
        $mapData[5][6] = ['type' => 'water', 'name' => 'Water'];

        // Mock position validation
        $this->positionService
            ->expects($this->once())
            ->method('isValidMapPosition')
            ->with($targetPosition, 7, 7)
            ->willReturn(true);

        // Mock movement execution to fail
        $executionResult = MovementExecutionResult::failed('Cannot move to impassable terrain', 'IMPASSABLE_TERRAIN');
        $this->turnDomainService
            ->expects($this->once())
            ->method('executeMovement')
            ->with($player, $targetPosition, ['type' => 'water', 'name' => 'Water'])
            ->willReturn($executionResult);

        $result = $this->movementService->movePlayer($player, $targetPosition, $mapData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Cannot move to impassable terrain', $result['message']);
        $this->assertEquals('IMPASSABLE_TERRAIN', $result['code']);
    }

    public function testMovePlayerWithInsufficientMovementPoints(): void
    {
        $player = Player::create(
            new PlayerId('player_123'),
            new Position(5, 5),
            'Test Player',
            1 // Only 1 movement point
        );

        $targetPosition = new Position(5, 6);
        $mapData = [];
        
        // Initialize map structure
        for ($i = 0; $i < 7; $i++) {
            for ($j = 0; $j < 7; $j++) {
                $mapData[$i][$j] = ['type' => 'plains', 'name' => 'Plains'];
            }
        }
        
        // Set target terrain to mountain
        $mapData[5][6] = ['type' => 'mountain', 'name' => 'Mountain'];

        // Mock position validation
        $this->positionService
            ->expects($this->once())
            ->method('isValidMapPosition')
            ->with($targetPosition, 7, 7)
            ->willReturn(true);

        // Mock movement execution to fail due to insufficient points
        $executionResult = MovementExecutionResult::failed('Insufficient movement points. Required: 3, Available: 1', 'INSUFFICIENT_MOVEMENT_POINTS');
        $this->turnDomainService
            ->expects($this->once())
            ->method('executeMovement')
            ->with($player, $targetPosition, ['type' => 'mountain', 'name' => 'Mountain'])
            ->willReturn($executionResult);

        $result = $this->movementService->movePlayer($player, $targetPosition, $mapData);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient movement points', $result['message']);
    }

    public function testCanPlayerMoveToPosition(): void
    {
        $player = Player::create(
            new PlayerId('player_123'),
            new Position(5, 5),
            'Test Player',
            3
        );

        // Mock canAffordMovement
        $this->turnDomainService
            ->expects($this->once())
            ->method('canAffordMovement')
            ->with($player, 2)
            ->willReturn(true);

        $result = $this->movementService->canPlayerMoveToPosition($player, 2);

        $this->assertTrue($result);
    }

    public function testArePositionsAdjacent(): void
    {
        $from = new Position(5, 5);
        $to = new Position(5, 6);

        // Mock arePositionsAdjacent
        $this->turnDomainService
            ->expects($this->once())
            ->method('arePositionsAdjacent')
            ->with($from, $to)
            ->willReturn(true);

        $result = $this->movementService->arePositionsAdjacent($from, $to);

        $this->assertTrue($result);
    }

    public function testGetTerrainMovementCost(): void
    {
        $terrainData = ['type' => 'mountain', 'name' => 'Mountain'];

        // Mock calculateMovementCost
        $this->turnDomainService
            ->expects($this->once())
            ->method('calculateMovementCost')
            ->with($terrainData)
            ->willReturn(3);

        $result = $this->movementService->getTerrainMovementCost($terrainData);

        $this->assertEquals(3, $result);
    }
}