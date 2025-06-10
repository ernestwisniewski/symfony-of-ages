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
use App\Domain\Game\Exception\GameAlreadyStartedException;
use App\Domain\Game\Exception\GameFullException;
use App\Domain\Game\Exception\GameNotStartedException;
use App\Domain\Game\Exception\InsufficientPlayersException;
use App\Domain\Game\Exception\NotPlayerTurnException;
use App\Domain\Game\Exception\PlayerAlreadyJoinedException;
use App\Domain\Game\Game;
use App\Domain\Game\Policy\GameStartPolicy;
use App\Domain\Game\Policy\PlayerJoinPolicy;
use App\Domain\Game\Policy\TurnEndPolicy;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameName;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Shared\ValueObject\UserId;
use Ecotone\Lite\EcotoneLite;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class GameTest extends TestCase
{
    private function getTestSupport()
    {
        return EcotoneLite::bootstrapFlowTesting([
            Game::class,
        ], [
            new GameStartPolicy(minPlayersRequired: 2),
            new PlayerJoinPolicy(maxPlayersAllowed: 4),
            new TurnEndPolicy(),
        ]);
    }

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
            new UserId(1),
            $createdAt
        );

        // When
        $testSupport = $this->getTestSupport();

        $recordedEvents = $testSupport
            ->sendCommand($command)
            ->getRecordedEvents();

        // Then
        $this->assertEquals(
            [new GameWasCreated(
                gameId: $gameId,
                playerId: $playerId,
                name: (string)$gameName,
                userId: 1,
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

        $testSupport = $this->getTestSupport();

        $testSupport
            ->withEventsFor($gameId, Game::class, [
                new GameWasCreated(
                    $gameId,
                    $creatorId,
                    'Test Game',
                    1,
                    Timestamp::now()->__toString()
                ),
            ])
            ->sendCommand(new JoinGameCommand(
                new GameId($gameId),
                new PlayerId($newPlayerId),
                new UserId(2),
                Timestamp::now()
            ));

        $this->assertEquals([
            new PlayerWasJoined($gameId, $newPlayerId, 2, Timestamp::now()->format())
        ], $testSupport->getRecordedEvents());
    }

    public function testThrowsExceptionWhenPlayerJoinsTwice(): void
    {
        $gameId = Uuid::v4()->toRfc4122();
        $playerId = Uuid::v4()->toRfc4122();

        $testSupport = $this->getTestSupport();

        $testSupport
            ->withEventsFor($gameId, Game::class, [
                new GameWasCreated(
                    $gameId,
                    $playerId,
                    'Test Game',
                    1,
                    Timestamp::now()->__toString()
                ),
            ]);

        $this->expectException(PlayerAlreadyJoinedException::class);
        $this->expectExceptionMessage("Player {$playerId} has already joined this game.");

        $testSupport->sendCommand(new JoinGameCommand(
            new GameId($gameId),
            new PlayerId($playerId),
            new UserId(1),
            Timestamp::now()
        ));
    }

    public function testThrowsExceptionWhenJoiningAfterGameStarted(): void
    {
        $gameId = Uuid::v4()->toRfc4122();
        $creatorId = Uuid::v4()->toRfc4122();
        $newPlayerId = Uuid::v4()->toRfc4122();

        $now = Timestamp::now()->__toString();

        $testSupport = $this->getTestSupport();

        $testSupport
            ->withEventsFor($gameId, Game::class, [
                new GameWasCreated(
                    $gameId,
                    $creatorId,
                    'Test Game',
                    1,
                    $now
                ),
                new GameWasStarted(
                    $gameId,
                    $now
                )
            ]);

        $this->expectException(GameAlreadyStartedException::class);
        $this->expectExceptionMessage("Game {$gameId} was already started.");

        $testSupport->sendCommand(new JoinGameCommand(
            new GameId($gameId),
            new PlayerId($newPlayerId),
            new UserId(2),
            Timestamp::now()
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
            new GameWasCreated($gameId, $existingPlayerIds[0], 'Test Game', 1, Timestamp::now()->__toString()),
        ];

        foreach (array_slice($existingPlayerIds, 1) as $i => $playerId) {
            $events[] = new PlayerWasJoined($gameId, $playerId, $i + 2, Timestamp::now()->format());
        }

        $testSupport = $this->getTestSupport();

        $testSupport->withEventsFor($gameId, Game::class, $events);

        $this->expectException(GameFullException::class);
        $this->expectExceptionMessage('Maximum 4 players allowed, game is full.');

        $testSupport->sendCommand(new JoinGameCommand(
            new GameId($gameId),
            new PlayerId($newPlayerId),
            new UserId(5),
            Timestamp::now()
        ));
    }

    public function testItStartsGameWithMinimumTwoPlayers(): void
    {
        $gameId = Uuid::v4()->toRfc4122();
        $player1 = Uuid::v4()->toRfc4122();
        $player2 = Uuid::v4()->toRfc4122();
        $now = Timestamp::now();

        $testSupport = $this->getTestSupport();

        $testSupport
            ->withEventsFor($gameId, Game::class, [
                new GameWasCreated($gameId, $player1, 'Test', 1, (string)$now),
                new PlayerWasJoined($gameId, $player2, 2, $now->format())
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

        $testSupport = $this->getTestSupport();

        $testSupport->withEventsFor($gameId, Game::class, [
            new GameWasCreated($gameId, $player1, 'Test', 1, (string)$now),
            new PlayerWasJoined($gameId, $player2, 2, $now->format()),
            new GameWasStarted($gameId, (string)$now),
        ]);

        $this->expectException(GameAlreadyStartedException::class);
        $this->expectExceptionMessage("Game {$gameId} was already started.");

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

        $testSupport = $this->getTestSupport();

        $testSupport->withEventsFor($gameId, Game::class, [
            new GameWasCreated($gameId, $player1, 'Test', 1, (string)$now),
        ]);

        $this->expectException(InsufficientPlayersException::class);
        $this->expectExceptionMessage('Minimum 2 players required, but only 1 joined.');

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

        $testSupport = $this->getTestSupport();

        $testSupport
            ->withEventsFor($gameId, Game::class, [
                new GameWasCreated($gameId, $player1, 'Test Game', 1, (string)$now),
                new PlayerWasJoined($gameId, $player2, 2, $now->format()),
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

        $testSupport = $this->getTestSupport();

        $testSupport->withEventsFor($gameId, Game::class, [
            new GameWasCreated($gameId, $player1, 'Test Game', 1, (string)$now),
        ]);

        $this->expectException(GameNotStartedException::class);
        $this->expectExceptionMessage("Game {$gameId} has not been started yet.");

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

        $testSupport = $this->getTestSupport();

        $testSupport->withEventsFor($gameId, Game::class, [
            new GameWasCreated($gameId, $player1, 'Test Game', 1, (string)$now),
            new PlayerWasJoined($gameId, $player2, 2, $now->format()),
            new GameWasStarted($gameId, (string)$now),
        ]);

        $this->expectException(NotPlayerTurnException::class);
        $this->expectExceptionMessage("It is not player {$player2}'s turn. Current active player is {$player1}.");

        $testSupport->sendCommand(new EndTurnCommand(
            new GameId($gameId),
            new PlayerId($player2),
            $now
        ));
    }
}
