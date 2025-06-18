<?php

namespace App\Tests\Unit\Infrastructure\ReadModel;

use App\Application\Game\Query\GetGameViewQuery;
use App\Domain\Game\Event\GameWasCreated;
use App\Domain\Game\Event\GameWasStarted;
use App\Domain\Game\Event\PlayerEndedTurn;
use App\Domain\Game\Event\PlayerWasJoined;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameStatus;
use App\Infrastructure\Exception\EntityNotFoundException;
use App\Infrastructure\Game\ReadModel\Doctrine\GameViewEntity;
use App\Infrastructure\Game\ReadModel\Doctrine\GameViewRepository;
use App\Infrastructure\Game\ReadModel\GameViewProjection;
use App\UI\Game\ViewModel\GameView;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

class GameViewProjectionTest extends TestCase
{
    public function testItCreatesGameViewEntity(): void
    {
        $gameId = 'game-1';
        $playerId = 'player-1';
        $now = new DateTimeImmutable()->format(DATE_ATOM);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(GameViewRepository::class);
        $mapper = $this->createMock(ObjectMapperInterface::class);

        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (GameViewEntity $entity) use ($gameId, $playerId) {
                return $entity->id === $gameId
                    && $entity->players === [$playerId]
                    && $entity->status === GameStatus::WAITING_FOR_PLAYERS->value;
            }));

        $entityManager->expects($this->once())->method('flush');

        $projection = new GameViewProjection($entityManager, $repository, $mapper);

        $projection->applyGameWasCreated(new GameWasCreated($gameId, $playerId, 'Test Game', 1, $now));
    }

    public function testItThrowWhenGameViewEntityNotFound(): void
    {
        $repository = $this->createMock(GameViewRepository::class);
        $repository->method('find')->willReturn(null);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('GameViewEntity for ID 11111111-1111-1111-1111-111111111111 not found');

        $projection = new GameViewProjection(
            $this->createMock(EntityManagerInterface::class),
            $repository,
            $this->createMock(ObjectMapperInterface::class)
        );

        $projection->getGameView(new GetGameViewQuery(new GameId('11111111-1111-1111-1111-111111111111')));
    }

    public function testUpdatesPlayersOnPlayerJoined(): void
    {
        $gameId = 'game-2';
        $existingEntity = new GameViewEntity($gameId, 'Test', 'player-1', 0, new DateTimeImmutable(), GameStatus::WAITING_FOR_PLAYERS->value, ['player-1'], 1);

        $repository = $this->createMock(GameViewRepository::class);
        $repository->method('find')->willReturn($existingEntity);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $projection = new GameViewProjection($entityManager, $repository, $this->createMock(ObjectMapperInterface::class));

        $projection->applyPlayerWasJoined(new PlayerWasJoined($gameId, 'player-2', 1, (new DateTimeImmutable())->format(DATE_ATOM)));

        $this->assertCount(2, $existingEntity->players);
        $this->assertContains('player-1', $existingEntity->players);
        $this->assertContains('player-2', $existingEntity->players);
    }

    public function testStartsGameAndUpdateFields(): void
    {
        $gameId = '11111111-1111-1111-1111-111111111111';
        $startedAt = new DateTimeImmutable('2025-06-01T12:00:00+00:00');

        $gameView = new GameViewEntity(
            id: $gameId,
            name: 'Test Game',
            activePlayer: 'player-1',
            currentTurn: 0,
            createdAt: new DateTimeImmutable('2025-06-01T11:00:00+00:00'),
            status: GameStatus::WAITING_FOR_PLAYERS->value,
            players: ['player-1', 'player-2'],
            userId: 1
        );

        $repository = $this->createMock(GameViewRepository::class);
        $repository->method('find')->willReturn($gameView);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $projection = new GameViewProjection(
            $entityManager,
            $repository,
            $this->createMock(ObjectMapperInterface::class)
        );

        $projection->applyGameWasStarted(new GameWasStarted(
            $gameId,
            $startedAt->format(DATE_ATOM)
        ));

        $this->assertEquals(GameStatus::IN_PROGRESS->value, $gameView->status);
        $this->assertEquals('player-1', $gameView->activePlayer);
        $this->assertEquals(1, $gameView->currentTurn);
        $this->assertEquals($startedAt, $gameView->startedAt);
        $this->assertEquals($startedAt, $gameView->currentTurnAt);
    }

    public function testEndsTurnAndUpdateActivePlayerAndTurnNumber(): void
    {
        $gameId = '11111111-1111-1111-1111-111111111111';
        $players = ['p1', 'p2', 'p3'];
        $currentTurn = 3;
        $endedAt = new DateTimeImmutable('2025-06-05T15:00:00+00:00');

        $gameView = new GameViewEntity(
            id: $gameId,
            name: 'Multi-turn test',
            activePlayer: 'p3',
            currentTurn: $currentTurn,
            createdAt: new DateTimeImmutable(),
            status: GameStatus::IN_PROGRESS->value,
            players: $players,
            userId: 1
        );

        $repository = $this->createMock(GameViewRepository::class);
        $repository->method('find')->willReturn($gameView);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $projection = new GameViewProjection(
            $entityManager,
            $repository,
            $this->createMock(ObjectMapperInterface::class)
        );

        $projection->applyPlayerEndedTurn(new PlayerEndedTurn(
            gameId: $gameId,
            playerId: 'p3',
            endedAt: $endedAt->format(DATE_ATOM)
        ));

        $this->assertEquals('p1', $gameView->activePlayer);
        $this->assertEquals($currentTurn + 1, $gameView->currentTurn);
        $this->assertEquals($endedAt, $gameView->currentTurnAt);
    }

    public function testMapsGameViewEntityToGameView(): void
    {
        $gameId = '11111111-1111-1111-1111-111111111111';

        $entity = new GameViewEntity(
            id: $gameId,
            name: 'Test Game',
            activePlayer: 'player-1',
            currentTurn: 2,
            createdAt: new DateTimeImmutable('2025-06-01T12:00:00+00:00'),
            status: 'in_progress',
            players: ['player-1', 'player-2'],
            userId: 1
        );
        $entity->startedAt = new DateTimeImmutable('2025-06-01T12:05:00+00:00');
        $entity->currentTurnAt = new DateTimeImmutable('2025-06-01T12:15:00+00:00');

        $repository = $this->createMock(GameViewRepository::class);
        $repository->method('find')->willReturn($entity);

        $expectedView = new GameView();
        $expectedView->id = $gameId;
        $expectedView->name = 'Test Game';
        $expectedView->activePlayer = 'player-1';
        $expectedView->currentTurn = 2;
        $expectedView->createdAt = '2025-06-01T12:00:00+00:00';
        $expectedView->players = ['player-1', 'player-2'];
        $expectedView->userId = 1;
        $expectedView->startedAt = '2025-06-01T12:05:00+00:00';
        $expectedView->currentTurnAt = '2025-06-01T12:15:00+00:00';

        $mapper = $this->createMock(ObjectMapperInterface::class);
        $mapper->method('map')->with($entity, GameView::class)->willReturn($expectedView);

        $projection = new GameViewProjection(
            $this->createMock(EntityManagerInterface::class),
            $repository,
            $mapper
        );

        $query = new GetGameViewQuery(
            new GameId($gameId)
        );

        $actualView = $projection->getGameView($query);

        $this->assertEquals($expectedView, $actualView);
    }
}
