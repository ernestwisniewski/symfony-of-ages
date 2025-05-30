<?php

namespace Tests\Unit\Domain\Game\Entity;

use App\Domain\Game\Entity\Game;
use App\Domain\Game\Event\GameCreated;
use App\Domain\Game\Event\GameEnded;
use App\Domain\Game\Event\GameStarted;
use App\Domain\Game\Event\PlayerJoinedGame;
use App\Domain\Game\Event\PlayerLeftGame;
use App\Domain\Game\Event\TurnChanged;
use App\Domain\Game\Exception\InvalidGameDataException;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameSettings;
use App\Domain\Game\ValueObject\GameStatus;
use App\Domain\Player\ValueObject\PlayerId;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for Game aggregate root
 */
class GameTest extends TestCase
{
    private GameId $gameId;
    private PlayerId $playerId1;
    private PlayerId $playerId2;
    private PlayerId $playerId3;

    protected function setUp(): void
    {
        $this->gameId = GameId::generate();
        $this->playerId1 = PlayerId::generate();
        $this->playerId2 = PlayerId::generate();
        $this->playerId3 = PlayerId::generate();
    }

    // =================== FACTORY METHODS ===================

    public function testCreateGame(): void
    {
        $game = Game::create($this->gameId, 'Test Game');

        $this->assertEquals($this->gameId, $game->getId());
        $this->assertEquals('Test Game', $game->getName());
        $this->assertEquals(GameStatus::WAITING_FOR_PLAYERS, $game->getStatus());
        $this->assertInstanceOf(GameSettings::class, $game->getSettings());
        $this->assertEmpty($game->getPlayers());
        $this->assertNull($game->getCurrentPlayerId());
        $this->assertEquals(1, $game->getCurrentTurnNumber());
        $this->assertInstanceOf(\DateTimeImmutable::class, $game->getCreatedAt());
    }

    public function testCreateGameWithCustomSettings(): void
    {
        $settings = GameSettings::createCustom(3, 6, false, 120, 50);
        $game = Game::createCustom($this->gameId, 'Custom Game', $settings);

        $this->assertEquals($settings, $game->getSettings());
        $this->assertEquals('Custom Game', $game->getName());
    }

    public function testCreateQuickPlayGame(): void
    {
        $game = Game::createQuickPlay($this->gameId, 'Quick Game');

        $this->assertEquals('Quick Game', $game->getName());
        $this->assertTrue($game->getSettings()->autoStart);
        $this->assertEquals(60, $game->getSettings()->turnTimeLimit);
    }

    public function testCreateGameEmitsGameCreatedEvent(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $events = $game->getDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(GameCreated::class, $events[0]);
        $this->assertEquals($this->gameId, $events[0]->gameId);
        $this->assertEquals('Test Game', $events[0]->gameName);
    }

    #[DataProvider('invalidGameDataProvider')]
    public function testCreateGameWithInvalidData(string $name, string $expectedExceptionMessage): void
    {
        $this->expectException(InvalidGameDataException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        Game::create($this->gameId, $name);
    }

    public static function invalidGameDataProvider(): array
    {
        return [
            'empty name' => ['', 'Game name cannot be empty'],
            'whitespace only' => ['   ', 'Game name cannot be empty'],
            'too long name' => [str_repeat('a', 101), 'Game name cannot exceed 100 characters']
        ];
    }

    // =================== GAME LIFECYCLE ===================

    public function testStartGameWithEnoughPlayers(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);

        $game->start();

        $this->assertEquals(GameStatus::IN_PROGRESS, $game->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $game->getStartedAt());
        $this->assertEquals($this->playerId1, $game->getCurrentPlayerId());
    }

    public function testStartGameEmitsGameStartedEvent(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);
        $game->clearDomainEvents(); // Clear creation event

        $game->start();

        $events = $game->getDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(GameStarted::class, $events[0]);
    }

    public function testCannotStartGameWithoutEnoughPlayers(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);

        $this->expectException(InvalidGameDataException::class);
        $this->expectExceptionMessage('Game cannot start');

        $game->start();
    }

    public function testPauseGame(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);
        $game->start();

        $game->pause();

        $this->assertEquals(GameStatus::PAUSED, $game->getStatus());
    }

    public function testCannotPauseGameNotInProgress(): void
    {
        $game = Game::create($this->gameId, 'Test Game');

        $this->expectException(InvalidGameDataException::class);
        $this->expectExceptionMessage('Game cannot be paused');

        $game->pause();
    }

    public function testResumeGame(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);
        $game->start();
        $game->pause();

        $game->resume();

        $this->assertEquals(GameStatus::IN_PROGRESS, $game->getStatus());
    }

    public function testEndGame(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);
        $game->start();

        $game->end('Manual end');

        $this->assertEquals(GameStatus::ENDED, $game->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $game->getEndedAt());
    }

    public function testEndGameEmitsGameEndedEvent(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);
        $game->start();
        $game->clearDomainEvents();

        $game->end('Test reason');

        $events = $game->getDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(GameEnded::class, $events[0]);
        $this->assertEquals('Test reason', $events[0]->reason);
    }

    // =================== PLAYER MANAGEMENT ===================

    public function testAddPlayer(): void
    {
        $game = Game::create($this->gameId, 'Test Game');

        $game->addPlayer($this->playerId1);

        $this->assertTrue($game->hasPlayer($this->playerId1));
        $this->assertEquals(1, $game->getPlayerCount());
        $this->assertContains($this->playerId1, $game->getPlayers());
    }

    public function testAddPlayerEmitsPlayerJoinedEvent(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->clearDomainEvents();

        $game->addPlayer($this->playerId1);

        $events = $game->getDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(PlayerJoinedGame::class, $events[0]);
        $this->assertEquals($this->playerId1, $events[0]->playerId);
    }

    public function testAutoStartGameWhenEnabled(): void
    {
        $settings = GameSettings::createCustom(2, 4, true); // Enable autoStart explicitly
        $game = Game::createCustom($this->gameId, 'Auto Start Game', $settings);
        $game->addPlayer($this->playerId1);

        $game->addPlayer($this->playerId2); // Should trigger auto-start

        $this->assertEquals(GameStatus::IN_PROGRESS, $game->getStatus());
    }

    public function testCannotAddPlayerTwice(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);

        $this->expectException(InvalidGameDataException::class);
        $this->expectExceptionMessage('Player ' . $this->playerId1->value . ' is already in the game');

        $game->addPlayer($this->playerId1);
    }

    public function testCannotAddPlayerToFullGame(): void
    {
        $settings = GameSettings::createCustom(2, 2); // Max 2 players
        $game = Game::createCustom($this->gameId, 'Full Game', $settings);
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);

        $this->expectException(InvalidGameDataException::class);
        $this->expectExceptionMessage('Game is full');

        $game->addPlayer($this->playerId3);
    }

    public function testRemovePlayer(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);

        $game->removePlayer($this->playerId1);

        $this->assertFalse($game->hasPlayer($this->playerId1));
        $this->assertTrue($game->hasPlayer($this->playerId2));
        $this->assertEquals(1, $game->getPlayerCount());
    }

    public function testRemovePlayerEmitsPlayerLeftEvent(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);
        $game->clearDomainEvents();

        $game->removePlayer($this->playerId1);

        $events = $game->getDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(PlayerLeftGame::class, $events[0]);
        $this->assertEquals($this->playerId1, $events[0]->playerId);
    }

    public function testRemoveCurrentPlayerAdvancesToNext(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);
        $game->start();

        $this->assertEquals($this->playerId1, $game->getCurrentPlayerId());

        $game->removePlayer($this->playerId1);

        $this->assertEquals($this->playerId2, $game->getCurrentPlayerId());
    }

    public function testGameEndsWhenNotEnoughPlayersRemain(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);
        $game->start();

        $game->removePlayer($this->playerId2); // Below minimum of 2 players

        $this->assertEquals(GameStatus::ENDED, $game->getStatus());
    }

    // =================== TURN MANAGEMENT ===================

    public function testNextTurn(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);
        $game->start();

        $this->assertEquals($this->playerId1, $game->getCurrentPlayerId());
        $this->assertEquals(1, $game->getCurrentTurnNumber());

        $game->nextTurn();

        $this->assertEquals($this->playerId2, $game->getCurrentPlayerId());
        $this->assertEquals(2, $game->getCurrentTurnNumber());
    }

    public function testNextTurnEmitsTurnChangedEvent(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);
        $game->start();
        $game->clearDomainEvents();

        $game->nextTurn();

        $events = $game->getDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TurnChanged::class, $events[0]);
        $this->assertEquals($this->playerId2, $events[0]->currentPlayerId);
        $this->assertEquals(2, $events[0]->turnNumber);
    }

    public function testNextTurnWrapsAroundToFirstPlayer(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);
        $game->start();

        $game->nextTurn(); // Player 2
        $game->nextTurn(); // Back to Player 1

        $this->assertEquals($this->playerId1, $game->getCurrentPlayerId());
        $this->assertEquals(3, $game->getCurrentTurnNumber());
    }

    public function testGameEndsWhenMaxTurnsReached(): void
    {
        $settings = GameSettings::createCustom(2, 2, false, null, 2); // Max 2 turns
        $game = Game::createCustom($this->gameId, 'Limited Turns', $settings);
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);
        $game->start();

        $game->nextTurn(); // Turn 2
        $game->nextTurn(); // Turn 3 - should end game

        $this->assertEquals(GameStatus::ENDED, $game->getStatus());
    }

    public function testSetCurrentPlayer(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);
        $game->start();

        $game->setCurrentPlayer($this->playerId2);

        $this->assertEquals($this->playerId2, $game->getCurrentPlayerId());
    }

    public function testCannotSetCurrentPlayerNotInGame(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);
        $game->start();

        $this->expectException(InvalidGameDataException::class);
        $this->expectExceptionMessage('Player ' . $this->playerId3->value . ' is not in the game');

        $game->setCurrentPlayer($this->playerId3);
    }

    // =================== STATE CHECKS ===================

    public function testStateChecks(): void
    {
        $game = Game::create($this->gameId, 'Test Game');

        // Initially waiting for players
        $this->assertTrue($game->canAcceptPlayers());
        $this->assertFalse($game->canStart());
        $this->assertFalse($game->isInProgress());
        $this->assertFalse($game->isFinished());

        // Add minimum players
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);
        $this->assertTrue($game->canStart());

        // Start game
        $game->start();
        $this->assertTrue($game->isInProgress());
        $this->assertFalse($game->canAcceptPlayers());

        // End game
        $game->end();
        $this->assertTrue($game->isFinished());
        $this->assertFalse($game->isInProgress());
    }

    public function testIsCurrentPlayer(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);
        $game->addPlayer($this->playerId2);
        $game->start();

        $this->assertTrue($game->isCurrentPlayer($this->playerId1));
        $this->assertFalse($game->isCurrentPlayer($this->playerId2));
    }

    // =================== SERIALIZATION ===================

    public function testToArray(): void
    {
        $game = Game::create($this->gameId, 'Test Game');
        $game->addPlayer($this->playerId1);

        $array = $game->toArray();

        $this->assertIsArray($array);
        $this->assertEquals($this->gameId->value, $array['id']);
        $this->assertEquals('Test Game', $array['name']);
        $this->assertEquals(GameStatus::WAITING_FOR_PLAYERS->value, $array['status']);
        $this->assertIsArray($array['settings']);
        $this->assertCount(1, $array['players']);
        $this->assertEquals($this->playerId1->value, $array['players'][0]);
        $this->assertEquals(1, $array['playerCount']);
    }

    public function testFromArray(): void
    {
        $originalGame = Game::create($this->gameId, 'Test Game');
        $originalGame->addPlayer($this->playerId1);
        $originalGame->addPlayer($this->playerId2);
        $originalGame->start();

        $array = $originalGame->toArray();
        $restoredGame = Game::fromArray($array);

        $this->assertEquals($originalGame->getId()->value, $restoredGame->getId()->value);
        $this->assertEquals($originalGame->getName(), $restoredGame->getName());
        $this->assertEquals($originalGame->getStatus(), $restoredGame->getStatus());
        $this->assertEquals($originalGame->getPlayerCount(), $restoredGame->getPlayerCount());
        $this->assertEquals($originalGame->getCurrentTurnNumber(), $restoredGame->getCurrentTurnNumber());
    }

    // =================== DOMAIN EVENTS ===================

    public function testDomainEventsManagement(): void
    {
        $game = Game::create($this->gameId, 'Test Game');

        $this->assertCount(1, $game->getDomainEvents()); // GameCreated

        $game->clearDomainEvents();
        $this->assertEmpty($game->getDomainEvents());

        $game->addPlayer($this->playerId1);
        $this->assertCount(1, $game->getDomainEvents()); // PlayerJoinedGame
    }
} 