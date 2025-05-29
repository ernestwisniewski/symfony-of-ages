<?php

namespace App\Tests\Unit\Application\Player\Service;

use App\Application\Player\Service\PlayerMovementService;
use App\Application\Player\Service\PlayerPositionService;
use App\Domain\Game\Service\MovementDomainService;
use App\Domain\Game\Service\MovementValidationResult;
use App\Domain\Game\ValueObject\PlayerId;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\ValueObject\Position;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PlayerMovementService
 */
class PlayerMovementServiceTest extends TestCase
{
    private PlayerMovementService $movementService;
    private PlayerPositionService|MockObject $positionService;
    private MovementDomainService|MockObject $domainService;

    protected function setUp(): void
    {
        $this->positionService = $this->createMock(PlayerPositionService::class);
        $this->domainService = $this->createMock(MovementDomainService::class);
        
        $this->movementService = new PlayerMovementService(
            $this->positionService,
            $this->domainService
        );
    }

    public function testMovePlayerSuccessfully(): void
    {
        $player = new Player(
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

        // Mock movement validation - the actual terrain data passed will be from mapData[5][6]
        $validationResult = MovementValidationResult::valid(2);
        $this->domainService
            ->expects($this->once())
            ->method('validateMovement')
            ->with($player->getPosition(), $targetPosition, ['type' => 'plains', 'name' => 'Plains'])
            ->willReturn($validationResult);

        $result = $this->movementService->movePlayer($player, $targetPosition, $mapData);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Moved to Plains', $result['message']);
        $this->assertEquals(1, $result['remainingMovement']); // 3 - 2 = 1
        $this->assertEquals($targetPosition, $player->getPosition());
    }

    public function testMovePlayerToInvalidPosition(): void
    {
        $player = new Player(
            new PlayerId('player_123'),
            new Position(5, 5),
            'Test Player'
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
        $player = new Player(
            new PlayerId('player_123'),
            new Position(5, 5),
            'Test Player'
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

        // Mock movement validation to return invalid
        $validationResult = MovementValidationResult::invalid('Cannot move to impassable terrain', 'IMPASSABLE_TERRAIN');
        $this->domainService
            ->expects($this->once())
            ->method('validateMovement')
            ->with($player->getPosition(), $targetPosition, ['type' => 'water', 'name' => 'Water'])
            ->willReturn($validationResult);

        $result = $this->movementService->movePlayer($player, $targetPosition, $mapData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Cannot move to impassable terrain', $result['message']);
        $this->assertEquals('IMPASSABLE_TERRAIN', $result['code']);
    }

    public function testMovePlayerWithInsufficientMovementPoints(): void
    {
        $player = new Player(
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

        // Mock movement validation to return valid but costly
        $validationResult = MovementValidationResult::valid(3); // Costs 3, but player has only 1
        $this->domainService
            ->expects($this->once())
            ->method('validateMovement')
            ->with($player->getPosition(), $targetPosition, ['type' => 'mountain', 'name' => 'Mountain'])
            ->willReturn($validationResult);

        $result = $this->movementService->movePlayer($player, $targetPosition, $mapData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Not enough movement points (need: 3, have: 1)', $result['message']);
    }

    public function testCanPlayerMoveToPosition(): void
    {
        $player = new Player(
            new PlayerId('player_123'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $this->assertTrue($this->movementService->canPlayerMoveToPosition($player, 2));
        $this->assertTrue($this->movementService->canPlayerMoveToPosition($player, 3));
        $this->assertFalse($this->movementService->canPlayerMoveToPosition($player, 4));
    }

    public function testArePositionsAdjacent(): void
    {
        $from = new Position(5, 5);
        $to = new Position(5, 6);

        $this->domainService
            ->expects($this->once())
            ->method('arePositionsAdjacent')
            ->with($from, $to)
            ->willReturn(true);

        $result = $this->movementService->arePositionsAdjacent($from, $to);

        $this->assertTrue($result);
    }

    public function testGetTerrainMovementCost(): void
    {
        $terrainData = ['type' => 'forest'];

        $this->domainService
            ->expects($this->once())
            ->method('calculateMovementCost')
            ->with($terrainData)
            ->willReturn(2);

        $result = $this->movementService->getTerrainMovementCost($terrainData);

        $this->assertEquals(2, $result);
    }
}