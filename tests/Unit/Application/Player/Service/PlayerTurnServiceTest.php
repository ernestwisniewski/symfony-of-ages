<?php

namespace Tests\Unit\Application\Player\Service;

use App\Application\Player\Service\PlayerTurnService;
use App\Domain\Player\Service\PlayerTurnDomainService;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Player\ValueObject\Position;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for PlayerTurnService
 */
class PlayerTurnServiceTest extends TestCase
{
    private PlayerTurnService $service;
    private PlayerTurnDomainService|MockObject $turnDomainService;

    protected function setUp(): void
    {
        $this->turnDomainService = $this->createMock(PlayerTurnDomainService::class);
        $this->service = new PlayerTurnService($this->turnDomainService);
    }

    public function testStartPlayerTurn(): void
    {
        $player = new Player(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $this->turnDomainService->expects($this->once())
            ->method('startNewTurn')
            ->with($player);

        $this->service->startPlayerTurn($player);
    }

    public function testEndPlayerTurn(): void
    {
        $player = new Player(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        // No domain service method for ending turn, so no expectations needed
        $this->service->endPlayerTurn($player);

        // Just ensure no exception is thrown
        $this->assertTrue(true);
    }

    public function testCanPlayerContinueTurn(): void
    {
        $player = new Player(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $this->turnDomainService->expects($this->once())
            ->method('canPlayerContinueTurn')
            ->with($player)
            ->willReturn(true);

        $result = $this->service->canPlayerContinueTurn($player);

        $this->assertTrue($result);
    }

    public function testGetRemainingMovementPoints(): void
    {
        $player = new Player(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $this->turnDomainService->expects($this->once())
            ->method('getRemainingMovement')
            ->with($player)
            ->willReturn(2);

        $result = $this->service->getRemainingMovementPoints($player);

        $this->assertEquals(2, $result);
    }

    public function testGetMaxMovementPoints(): void
    {
        $player = new Player(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $result = $this->service->getMaxMovementPoints($player);

        $this->assertEquals(3, $result);
    }

    public function testShouldEndTurn(): void
    {
        $player = new Player(
            new PlayerId('test_player'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $this->turnDomainService->expects($this->once())
            ->method('shouldEndTurn')
            ->with($player)
            ->willReturn(false);

        $result = $this->service->shouldEndTurn($player);

        $this->assertFalse($result);
    }
} 