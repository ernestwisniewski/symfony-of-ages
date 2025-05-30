<?php

namespace Tests\Unit\Application\Player\Service;

use App\Application\Player\Service\PlayerCreationService;
use App\Application\Player\Service\PlayerPositionService;
use App\Domain\Player\Factory\PlayerFactory;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Player\ValueObject\Position;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for PlayerCreationService
 */
class PlayerCreationServiceTest extends TestCase
{
    private PlayerCreationService $service;
    private PlayerPositionService|MockObject $positionService;
    private PlayerFactory|MockObject $playerFactory;

    protected function setUp(): void
    {
        $this->positionService = $this->createMock(PlayerPositionService::class);
        $this->playerFactory = $this->createMock(PlayerFactory::class);
        $this->service = new PlayerCreationService($this->positionService, $this->playerFactory);
    }

    public function testCreatePlayer(): void
    {
        $name = 'Test Player';
        $mapRows = 10;
        $mapCols = 10;
        $mapData = [
            [['type' => 'plains', 'name' => 'Plains']]
        ];
        $maxMovementPoints = 3;

        $expectedPosition = new Position(5, 5);
        $expectedPlayer = Player::create(
            new PlayerId('player_123'),
            $expectedPosition,
            $name,
            $maxMovementPoints
        );

        $this->positionService->expects($this->once())
            ->method('generateValidStartingPosition')
            ->with($mapRows, $mapCols, $mapData)
            ->willReturn($expectedPosition);

        $this->playerFactory->expects($this->once())
            ->method('createPlayer')
            ->with($name, $expectedPosition, $maxMovementPoints)
            ->willReturn($expectedPlayer);

        $result = $this->service->createPlayer($name, $mapRows, $mapCols, $mapData, $maxMovementPoints);

        $this->assertSame($expectedPlayer, $result);
    }

    public function testCreatePlayerWithDefaultMovementPoints(): void
    {
        $name = 'Test Player';
        $mapRows = 10;
        $mapCols = 10;
        $mapData = [
            [['type' => 'plains', 'name' => 'Plains']]
        ];

        $expectedPosition = new Position(5, 5);
        $expectedPlayer = Player::create(
            new PlayerId('player_123'),
            $expectedPosition,
            $name,
            3 // default movement points
        );

        $this->positionService->expects($this->once())
            ->method('generateValidStartingPosition')
            ->willReturn($expectedPosition);

        $this->playerFactory->expects($this->once())
            ->method('createPlayer')
            ->with($name, $expectedPosition, 3)
            ->willReturn($expectedPlayer);

        $result = $this->service->createPlayer($name, $mapRows, $mapCols, $mapData);

        $this->assertSame($expectedPlayer, $result);
    }

    public function testCreatePlayerWithPosition(): void
    {
        $name = 'Test Player';
        $row = 3;
        $col = 7;
        $maxMovementPoints = 5;

        $expectedPosition = new Position($row, $col);
        $expectedPlayer = Player::create(
            new PlayerId('player_456'),
            $expectedPosition,
            $name,
            $maxMovementPoints
        );

        $this->playerFactory->expects($this->once())
            ->method('createPlayer')
            ->with($name, $expectedPosition, $maxMovementPoints)
            ->willReturn($expectedPlayer);

        $result = $this->service->createPlayerWithPosition($name, $row, $col, $maxMovementPoints);

        $this->assertSame($expectedPlayer, $result);
    }

    public function testCreatePlayerWithPositionAndDefaults(): void
    {
        $name = 'Test Player';
        $row = 3;
        $col = 7;

        $expectedPosition = new Position($row, $col);
        $expectedPlayer = Player::create(
            new PlayerId('player_456'),
            $expectedPosition,
            $name,
            3 // default movement points
        );

        $this->playerFactory->expects($this->once())
            ->method('createPlayer')
            ->with($name, $expectedPosition, 3)
            ->willReturn($expectedPlayer);

        $result = $this->service->createPlayerWithPosition($name, $row, $col);

        $this->assertSame($expectedPlayer, $result);
    }

    public function testCreateTestPlayer(): void
    {
        $name = 'Test Player';
        $position = new Position(10, 10);

        $expectedPlayer = Player::create(
            new PlayerId('test_player'),
            $position,
            $name,
            3
        );

        $this->playerFactory->expects($this->once())
            ->method('createTestPlayer')
            ->with($name, $position)
            ->willReturn($expectedPlayer);

        $result = $this->service->createTestPlayer($name, $position);

        $this->assertSame($expectedPlayer, $result);
    }

    public function testCreateTestPlayerWithDefaults(): void
    {
        $expectedPlayer = Player::create(
            new PlayerId('test_player'),
            new Position(0, 0),
            'Test Player',
            3
        );

        $this->playerFactory->expects($this->once())
            ->method('createTestPlayer')
            ->with('Test Player', null)
            ->willReturn($expectedPlayer);

        $result = $this->service->createTestPlayer();

        $this->assertSame($expectedPlayer, $result);
    }
} 