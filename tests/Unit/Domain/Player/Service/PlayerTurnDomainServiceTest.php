<?php

namespace Tests\Unit\Domain\Player\Service;

use App\Domain\Player\Service\PlayerTurnDomainService;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Player\ValueObject\Position;
use App\Domain\Shared\Service\HexGridService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PlayerTurnDomainService
 */
class PlayerTurnDomainServiceTest extends TestCase
{
    private PlayerTurnDomainService $service;
    private HexGridService $hexGridService;

    protected function setUp(): void
    {
        $this->hexGridService = new HexGridService();
        $this->service = new PlayerTurnDomainService($this->hexGridService);
    }

    public function testStartNewTurn(): void
    {
        $player = Player::create(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        // Use movement points first
        $player->moveTo(new Position(5, 6), 2);
        $this->assertEquals(1, $player->currentMovementPoints);

        // Start new turn - should restore movement points
        $this->service->startNewTurn($player);

        $this->assertEquals(3, $player->currentMovementPoints);
    }

    public function testCanStartNewTurn(): void
    {
        $player = Player::create(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $result = $this->service->canStartNewTurn($player);

        $this->assertTrue($result);
    }

    public function testCanPlayerContinueTurnWithMovementPoints(): void
    {
        $player = Player::create(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $result = $this->service->canPlayerContinueTurn($player);

        $this->assertTrue($result);
    }

    public function testCanPlayerContinueTurnWithoutMovementPoints(): void
    {
        $player = Player::create(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        // Use all movement points
        $player->moveTo(new Position(5, 6), 3);

        $result = $this->service->canPlayerContinueTurn($player);

        $this->assertFalse($result);
    }

    public function testShouldEndTurnWithMovementPoints(): void
    {
        $player = Player::create(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $result = $this->service->shouldEndTurn($player);

        $this->assertFalse($result);
    }

    public function testShouldEndTurnWithoutMovementPoints(): void
    {
        $player = Player::create(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        // Use all movement points
        $player->moveTo(new Position(5, 6), 3);

        $result = $this->service->shouldEndTurn($player);

        $this->assertTrue($result);
    }

    public function testGetRemainingMovement(): void
    {
        $player = Player::create(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        // Use some movement points
        $player->moveTo(new Position(5, 6), 1);

        $result = $this->service->getRemainingMovementPoints($player);

        $this->assertEquals(2, $result);
    }

    public function testCalculateMovementEfficiencyWithFullMovement(): void
    {
        $player = Player::create(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $result = $this->service->calculateMovementEfficiency($player);

        $this->assertEquals(0.0, $result); // No movement used yet
    }

    public function testCalculateMovementEfficiencyWithPartialMovement(): void
    {
        $player = Player::create(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        // Use 1 movement point
        $player->moveTo(new Position(5, 6), 1);

        $result = $this->service->calculateMovementEfficiency($player);

        $this->assertEquals(1.0 / 3.0, $result, '', 0.01); // 33.33% efficiency
    }

    public function testCalculateMovementEfficiencyWithAllMovementUsed(): void
    {
        $player = Player::create(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        // Use all movement points
        $player->moveTo(new Position(5, 6), 3);

        $result = $this->service->calculateMovementEfficiency($player);

        $this->assertEquals(1.0, $result); // 100% efficiency
    }
} 