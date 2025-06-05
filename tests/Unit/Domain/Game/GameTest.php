<?php

namespace App\Tests\Unit\Domain\Game;

use App\Application\Game\Command\CreateGameCommand;
use App\Application\Game\Command\EndTurnCommand;
use App\Application\Game\Command\JoinGameCommand;
use App\Application\Game\Command\StartGameCommand;
use App\Domain\Game\Event\GameWasCreated;
use App\Domain\Game\Event\GameWasStarted;
use App\Domain\Game\Event\PlayerEndedTurn;
use App\Domain\Game\Event\PlayerWasJoined;
use App\Domain\Game\Game;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameName;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use Ecotone\Lite\EcotoneLite;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class GameTest extends TestCase
{
    public function testCreatesGameAndEmitsEvent(): void
    {
        // Given
        $gameId = Uuid::v4()->toRfc4122();
        $playerId = Uuid::v4()->toRfc4122();
        $gameName = new GameName('Test Game');
        $createdAt = Timestamp::now();

        $command = new CreateGameCommand(
            new GameId($gameId),
            new PlayerId($playerId),
            $gameName,
            $createdAt
        );

        // When
        $testSupport = EcotoneLite::bootstrapFlowTesting([
            Game::class,
        ]);

        $recordedEvents = $testSupport
            ->sendCommand($command)
            ->getRecordedEvents();

        // Then
        $this->assertEquals(
            [new GameWasCreated(
                gameId: $gameId,
                playerId: $playerId,
                name: (string)$gameName,
                createdAt: (string)$createdAt
            )],
            $recordedEvents
        );
    }

    public function testItAllowsPlayerToJoinGame(): void
    {
        $gameId = Uuid::v4()->toRfc4122();
        $creatorId = Uuid::v4()->toRfc4122();
        $newPlayerId = Uuid::v4()->toRfc4122();

        $testSupport = EcotoneLite::bootstrapFlowTesting([Game::class]);

        $testSupport
            ->withEventsFor($gameId, Game::class, [
                new GameWasCreated(
                    $gameId,
                    $creatorId,
                    'Test Game',
                    Timestamp::now()->__toString()
                ),
            ])
            ->sendCommand(new JoinGameCommand(
                new GameId($gameId),
                new PlayerId($newPlayerId)
            ));

        $this->assertEquals([
            new PlayerWasJoined($gameId, $newPlayerId)
        ], $testSupport->getRecordedEvents());
    }

    public function testThrowsExceptionWhenPlayerJoinsTwice(): void
    {
        $gameId = Uuid::v4()->toRfc4122();
        $playerId = Uuid::v4()->toRfc4122();

        $testSupport = EcotoneLite::bootstrapFlowTesting([Game::class]);

        $testSupport
            ->withEventsFor($gameId, Game::class, [
                new GameWasCreated(
                    $gameId,
                    $playerId,
                    'Test Game',
                    Timestamp::now()->__toString()
                ),
            ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Player');

        $testSupport->sendCommand(new JoinGameCommand(
            new GameId($gameId),
            new PlayerId($playerId)
        ));
    }

    public function testThrowsExceptionWhenJoiningAfterGameStarted(): void
    {
        $gameId = Uuid::v4()->toRfc4122();
        $creatorId = Uuid::v4()->toRfc4122();
        $newPlayerId = Uuid::v4()->toRfc4122();

        $now = Timestamp::now()->__toString();

        $testSupport = EcotoneLite::bootstrapFlowTesting([Game::class]);

        $testSupport
            ->withEventsFor($gameId, Game::class, [
                new GameWasCreated(
                    $gameId,
                    $creatorId,
                    'Test Game',
                    $now
                ),
                new GameWasStarted(
                    $gameId,
                    $now
                )
            ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('already started');

        $testSupport->sendCommand(new JoinGameCommand(
            new GameId($gameId),
            new PlayerId($newPlayerId)
        ));
    }

    public function testThrowsExceptionWhenGameHasMaxPlayers(): void
    {
        $gameId = Uuid::v4()->toRfc4122();
        $existingPlayerIds = [
            Uuid::v4()->toRfc4122(),
            Uuid::v4()->toRfc4122(),
            Uuid::v4()->toRfc4122(),
            Uuid::v4()->toRfc4122(), // 4 graczy = MAX
        ];
        $newPlayerId = Uuid::v4()->toRfc4122();

        $events = [
            new GameWasCreated($gameId, $existingPlayerIds[0], 'Test Game', Timestamp::now()->__toString()),
        ];

        foreach (array_slice($existingPlayerIds, 1) as $playerId) {
            $events[] = new PlayerWasJoined($gameId, $playerId);
        }

        $testSupport = EcotoneLite::bootstrapFlowTesting([Game::class]);

        $testSupport->withEventsFor($gameId, Game::class, $events);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('game is full');

        $testSupport->sendCommand(new JoinGameCommand(
            new GameId($gameId),
            new PlayerId($newPlayerId)
        ));
    }

    public function testItStartsGameWithMinimumTwoPlayers(): void
    {
        $gameId = Uuid::v4()->toRfc4122();
        $player1 = Uuid::v4()->toRfc4122();
        $player2 = Uuid::v4()->toRfc4122();
        $now = Timestamp::now();

        $testSupport = EcotoneLite::bootstrapFlowTesting([Game::class]);

        $testSupport
            ->withEventsFor($gameId, Game::class, [
                new GameWasCreated($gameId, $player1, 'Test', (string)$now),
                new PlayerWasJoined($gameId, $player2)
            ])
            ->sendCommand(new StartGameCommand(
                new GameId($gameId),
                $now
            ));

        $this->assertEquals([
            new GameWasStarted($gameId, (string)$now)
        ], $testSupport->getRecordedEvents());
    }

    public function testItThrowsExceptionWhenStartingTwice(): void
    {
        $gameId = Uuid::v4()->toRfc4122();
        $player1 = Uuid::v4()->toRfc4122();
        $player2 = Uuid::v4()->toRfc4122();
        $now = Timestamp::now();

        $testSupport = EcotoneLite::bootstrapFlowTesting([Game::class]);

        $testSupport->withEventsFor($gameId, Game::class, [
            new GameWasCreated($gameId, $player1, 'Test', (string)$now),
            new PlayerWasJoined($gameId, $player2),
            new GameWasStarted($gameId, (string)$now),
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('already started');

        $testSupport->sendCommand(new StartGameCommand(
            new GameId($gameId),
            $now
        ));
    }

    public function testThrowsExceptionWhenNotEnoughPlayers(): void
    {
        $gameId = Uuid::v4()->toRfc4122();
        $player1 = Uuid::v4()->toRfc4122();
        $now = Timestamp::now();

        $testSupport = EcotoneLite::bootstrapFlowTesting([Game::class]);

        $testSupport->withEventsFor($gameId, Game::class, [
            new GameWasCreated($gameId, $player1, 'Test', (string)$now),
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Minimum 2 players required');

        $testSupport->sendCommand(new StartGameCommand(
            new GameId($gameId),
            $now
        ));
    }

    public function testAllowsActivePlayerToEndTurn(): void
    {
        $gameId = Uuid::v4()->toRfc4122();
        $player1 = Uuid::v4()->toRfc4122();
        $player2 = Uuid::v4()->toRfc4122();
        $now = Timestamp::now();

        $testSupport = EcotoneLite::bootstrapFlowTesting([Game::class]);

        $testSupport
            ->withEventsFor($gameId, Game::class, [
                new GameWasCreated($gameId, $player1, 'Test Game', (string)$now),
                new PlayerWasJoined($gameId, $player2),
                new GameWasStarted($gameId, (string)$now),
            ])
            ->sendCommand(new EndTurnCommand(
                new GameId($gameId),
                new PlayerId($player1),
                $now
            ));

        $this->assertEquals([
            new PlayerEndedTurn($gameId, $player1, (string)$now)
        ], $testSupport->getRecordedEvents());
    }

    public function testThrowsExceptionIfGameNotStarted(): void
    {
        $gameId = Uuid::v4()->toRfc4122();
        $player1 = Uuid::v4()->toRfc4122();
        $now = Timestamp::now();

        $testSupport = EcotoneLite::bootstrapFlowTesting([Game::class]);

        $testSupport->withEventsFor($gameId, Game::class, [
            new GameWasCreated($gameId, $player1, 'Test Game', (string)$now),
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not been started');

        $testSupport->sendCommand(new EndTurnCommand(
            new GameId($gameId),
            new PlayerId($player1),
            $now
        ));
    }

    public function testThrowsExceptionIfNotPlayersTurn(): void
    {
        $gameId = Uuid::v4()->toRfc4122();
        $player1 = Uuid::v4()->toRfc4122();
        $player2 = Uuid::v4()->toRfc4122();
        $now = Timestamp::now();

        $testSupport = EcotoneLite::bootstrapFlowTesting([Game::class]);

        $testSupport->withEventsFor($gameId, Game::class, [
            new GameWasCreated($gameId, $player1, 'Test Game', (string)$now),
            new PlayerWasJoined($gameId, $player2),
            new GameWasStarted($gameId, (string)$now),
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not player');

        $testSupport->sendCommand(new EndTurnCommand(
            new GameId($gameId),
            new PlayerId($player2),
            $now
        ));
    }
}
