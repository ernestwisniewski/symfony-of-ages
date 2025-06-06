<?php

namespace App\Tests\Unit\Domain\Game\Policy;

use App\Domain\Game\Exception\GameAlreadyStartedException;
use App\Domain\Game\Exception\GameFullException;
use App\Domain\Game\Exception\PlayerAlreadyJoinedException;
use App\Domain\Game\Policy\PlayerJoinPolicy;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use PHPUnit\Framework\TestCase;

final class PlayerJoinPolicyTest extends TestCase
{
    private PlayerJoinPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new PlayerJoinPolicy(maxPlayersAllowed: 4);
    }

    public function testCanJoinWithValidConditions(): void
    {
        $playerId = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $existingPlayers = [];
        $startedAt = null;

        $result = $this->policy->canJoin($playerId, $existingPlayers, $startedAt);

        $this->assertTrue($result);
    }

    public function testCannotJoinWhenGameStarted(): void
    {
        $playerId = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $existingPlayers = [];
        $startedAt = Timestamp::now();

        $result = $this->policy->canJoin($playerId, $existingPlayers, $startedAt);

        $this->assertFalse($result);
    }

    public function testCannotJoinWhenPlayerAlreadyExists(): void
    {
        $playerId = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $existingPlayers = [$playerId];
        $startedAt = null;

        $result = $this->policy->canJoin($playerId, $existingPlayers, $startedAt);

        $this->assertFalse($result);
    }

    public function testCannotJoinWhenGameIsFull(): void
    {
        $playerId = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $existingPlayers = [
            new PlayerId('123e4567-e89b-12d3-a456-426614174001'),
            new PlayerId('123e4567-e89b-12d3-a456-426614174002'),
            new PlayerId('123e4567-e89b-12d3-a456-426614174003'),
            new PlayerId('123e4567-e89b-12d3-a456-426614174004'),
        ];
        $startedAt = null;

        $result = $this->policy->canJoin($playerId, $existingPlayers, $startedAt);

        $this->assertFalse($result);
    }

    public function testValidateJoinThrowsExceptionWhenGameStarted(): void
    {
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174000');
        $playerId = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $existingPlayers = [];
        $startedAt = Timestamp::now();

        $this->expectException(GameAlreadyStartedException::class);
        $this->expectExceptionMessage("Game {$gameId} was already started.");

        $this->policy->validateJoin($gameId, $playerId, $existingPlayers, $startedAt);
    }

    public function testValidateJoinThrowsExceptionWhenPlayerAlreadyJoined(): void
    {
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174000');
        $playerId = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $existingPlayers = [$playerId];
        $startedAt = null;

        $this->expectException(PlayerAlreadyJoinedException::class);
        $this->expectExceptionMessage("Player {$playerId} has already joined this game.");

        $this->policy->validateJoin($gameId, $playerId, $existingPlayers, $startedAt);
    }

    public function testValidateJoinThrowsExceptionWhenGameIsFull(): void
    {
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174000');
        $playerId = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $existingPlayers = [
            new PlayerId('123e4567-e89b-12d3-a456-426614174001'),
            new PlayerId('123e4567-e89b-12d3-a456-426614174002'),
            new PlayerId('123e4567-e89b-12d3-a456-426614174003'),
            new PlayerId('123e4567-e89b-12d3-a456-426614174004'),
        ];
        $startedAt = null;

        $this->expectException(GameFullException::class);
        $this->expectExceptionMessage('Maximum 4 players allowed, game is full.');

        $this->policy->validateJoin($gameId, $playerId, $existingPlayers, $startedAt);
    }

    public function testValidateJoinPassesWithValidConditions(): void
    {
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174000');
        $playerId = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $existingPlayers = [];
        $startedAt = null;

        // Should not throw any exception
        $this->policy->validateJoin($gameId, $playerId, $existingPlayers, $startedAt);

        $this->assertTrue(true); // If we reach here, test passed
    }
} 