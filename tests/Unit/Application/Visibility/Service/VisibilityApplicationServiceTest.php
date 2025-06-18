<?php

namespace App\Tests\Unit\Application\Visibility\Service;

use App\Application\Visibility\Command\UpdateVisibilityCommand;
use App\Application\Visibility\Query\GetGameVisibilityQuery;
use App\Application\Visibility\Query\GetPlayerVisibilityQuery;
use App\Application\Visibility\Service\VisibilityApplicationService;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\UI\Visibility\ViewModel\PlayerVisibilityView;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;
use PHPUnit\Framework\TestCase;

class VisibilityApplicationServiceTest extends TestCase
{
    private VisibilityApplicationService $service;
    private CommandBus $commandBus;
    private QueryBus $queryBus;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->queryBus = $this->createMock(QueryBus::class);
        $this->service = new VisibilityApplicationService($this->commandBus, $this->queryBus);
    }

    public function testUpdatePlayerVisibility(): void
    {
        $playerId = new PlayerId('123e4567-e89b-12d3-a456-426614174001');
        
        $units = [
            (object) [
                'position' => new \App\Domain\Shared\ValueObject\Position(5, 5),
                'type' => 'warrior'
            ]
        ];
        
        $cities = [
            (object) [
                'position' => new \App\Domain\Shared\ValueObject\Position(6, 6),
                'level' => 1
            ]
        ];

        $this->commandBus->expects($this->once())
            ->method('send')
            ->with($this->callback(function (UpdateVisibilityCommand $command) {
                return $command->playerId === '123e4567-e89b-12d3-a456-426614174001' &&
                       count($command->unitPositions) === 1 &&
                       count($command->cityPositions) === 1;
            }));

        $this->service->updatePlayerVisibility($playerId, $units, $cities);
    }

    public function testGetPlayerVisibility(): void
    {
        $playerId = new PlayerId('123e4567-e89b-12d3-a456-426614174001');
        
        $expectedVisibility = [
            new \App\UI\Visibility\ViewModel\PlayerVisibilityView('123e4567-e89b-12d3-a456-426614174001', 5, 5, 'active', '2024-01-01T00:00:00Z')
        ];

        $this->queryBus->expects($this->once())
            ->method('send')
            ->with($this->callback(function (\App\Application\Visibility\Query\GetPlayerVisibilityQuery $query) {
                return (string)$query->playerId === '123e4567-e89b-12d3-a456-426614174001';
            }))
            ->willReturn($expectedVisibility);

        $result = $this->service->getPlayerVisibility($playerId);
        
        $this->assertEquals($expectedVisibility, $result);
    }

    public function testGetGameVisibility(): void
    {
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174002');
        
        $expectedVisibility = [
            new PlayerVisibilityView('123e4567-e89b-12d3-a456-426614174001', 5, 5, 'active', '2024-01-01T00:00:00Z'),
            new PlayerVisibilityView('123e4567-e89b-12d3-a456-426614174003', 6, 6, 'discovered', '2024-01-01T00:00:00Z')
        ];

        $this->queryBus->expects($this->once())
            ->method('send')
            ->with($this->callback(function (GetGameVisibilityQuery $query) {
                return (string)$query->gameId === '123e4567-e89b-12d3-a456-426614174002';
            }))
            ->willReturn($expectedVisibility);

        $result = $this->service->getGameVisibility($gameId);
        
        $this->assertEquals($expectedVisibility, $result);
    }

    public function testIsHexVisibleForPlayer(): void
    {
        $playerId = new PlayerId('123e4567-e89b-12d3-a456-426614174001');
        
        $visibility = [
            new PlayerVisibilityView('123e4567-e89b-12d3-a456-426614174001', 5, 5, 'active', '2024-01-01T00:00:00Z'),
            new PlayerVisibilityView('123e4567-e89b-12d3-a456-426614174001', 6, 6, 'discovered', '2024-01-01T00:00:00Z')
        ];

        $this->queryBus->expects($this->exactly(3))
            ->method('send')
            ->willReturn($visibility);

        $this->assertTrue($this->service->isHexVisibleForPlayer(5, 5, $playerId));
        $this->assertFalse($this->service->isHexVisibleForPlayer(6, 6, $playerId));
        $this->assertFalse($this->service->isHexVisibleForPlayer(7, 7, $playerId));
    }

    public function testIsHexDiscoveredForPlayer(): void
    {
        $playerId = new PlayerId('123e4567-e89b-12d3-a456-426614174001');
        
        $visibility = [
            new PlayerVisibilityView('123e4567-e89b-12d3-a456-426614174001', 5, 5, 'active', '2024-01-01T00:00:00Z'),
            new PlayerVisibilityView('123e4567-e89b-12d3-a456-426614174001', 6, 6, 'discovered', '2024-01-01T00:00:00Z')
        ];

        $this->queryBus->expects($this->exactly(3))
            ->method('send')
            ->willReturn($visibility);

        $this->assertTrue($this->service->isHexDiscoveredForPlayer(5, 5, $playerId));
        $this->assertTrue($this->service->isHexDiscoveredForPlayer(6, 6, $playerId));
        $this->assertFalse($this->service->isHexDiscoveredForPlayer(7, 7, $playerId));
    }

    public function testUpdatePlayerVisibilityWithEmptyData(): void
    {
        $playerId = new PlayerId('123e4567-e89b-12d3-a456-426614174001');

        $this->commandBus->expects($this->once())
            ->method('send')
            ->with($this->callback(function (UpdateVisibilityCommand $command) {
                return count($command->unitPositions) === 0 &&
                       count($command->cityPositions) === 0;
            }));

        $this->service->updatePlayerVisibility($playerId, [], []);
    }

    public function testUpdatePlayerVisibilityWithMultipleUnits(): void
    {
        $playerId = new PlayerId('123e4567-e89b-12d3-a456-426614174001');
        
        $units = [
            (object) ['position' => ['x' => 5, 'y' => 5], 'type' => 'warrior'],
            (object) ['position' => ['x' => 6, 'y' => 6], 'type' => 'scout']
        ];

        $this->commandBus->expects($this->once())
            ->method('send')
            ->with($this->callback(function (UpdateVisibilityCommand $command) {
                return count($command->unitPositions) === 2;
            }));

        $this->service->updatePlayerVisibility($playerId, $units, []);
    }
} 