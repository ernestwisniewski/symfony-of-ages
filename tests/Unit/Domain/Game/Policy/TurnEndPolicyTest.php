<?php

namespace App\Tests\Unit\Domain\Game\Policy;

use App\Domain\Game\Exception\GameNotStartedException;
use App\Domain\Game\Exception\NotPlayerTurnException;
use App\Domain\Game\Policy\TurnEndPolicy;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use PHPUnit\Framework\TestCase;

final class TurnEndPolicyTest extends TestCase
{
    private TurnEndPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new TurnEndPolicy();
    }

    public function testCanEndTurnWithValidConditions(): void
    {
        $playerId = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $activePlayer = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $startedAt = Timestamp::now();

        $result = $this->policy->canEndTurn($playerId, $activePlayer, $startedAt);

        $this->assertTrue($result);
    }

    public function testCannotEndTurnWhenGameNotStarted(): void
    {
        $playerId = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $activePlayer = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $startedAt = null;

        $result = $this->policy->canEndTurn($playerId, $activePlayer, $startedAt);

        $this->assertFalse($result);
    }

    public function testCannotEndTurnWhenNotPlayersTurn(): void
    {
        $playerId = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $activePlayer = new PlayerId('323e4567-e89b-12d3-a456-426614174000'); // Different player
        $startedAt = Timestamp::now();

        $result = $this->policy->canEndTurn($playerId, $activePlayer, $startedAt);

        $this->assertFalse($result);
    }

    public function testValidateEndTurnThrowsExceptionWhenGameNotStarted(): void
    {
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174000');
        $playerId = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $activePlayer = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $startedAt = null;

        $this->expectException(GameNotStartedException::class);
        $this->expectExceptionMessage("Game {$gameId} has not been started yet.");

        $this->policy->validateEndTurn($gameId, $playerId, $activePlayer, $startedAt);
    }

    public function testValidateEndTurnThrowsExceptionWhenNotPlayersTurn(): void
    {
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174000');
        $playerId = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $activePlayer = new PlayerId('323e4567-e89b-12d3-a456-426614174000'); // Different player
        $startedAt = Timestamp::now();

        $this->expectException(NotPlayerTurnException::class);
        $this->expectExceptionMessage("It is not player {$playerId}'s turn. Current active player is {$activePlayer}.");

        $this->policy->validateEndTurn($gameId, $playerId, $activePlayer, $startedAt);
    }

    public function testValidateEndTurnPassesWithValidConditions(): void
    {
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174000');
        $playerId = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $activePlayer = new PlayerId('223e4567-e89b-12d3-a456-426614174000');
        $startedAt = Timestamp::now();

        // Should not throw any exception
        $this->policy->validateEndTurn($gameId, $playerId, $activePlayer, $startedAt);

        $this->assertTrue(true); // If we reach here, test passed
    }
} 