<?php

namespace App\Tests\Unit\Domain\Game\Policy;

use App\Domain\Game\Exception\GameAlreadyStartedException;
use App\Domain\Game\Exception\InsufficientPlayersException;
use App\Domain\Game\Policy\GameStartPolicy;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Shared\ValueObject\Timestamp;
use PHPUnit\Framework\TestCase;

final class GameStartPolicyTest extends TestCase
{
    private GameStartPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new GameStartPolicy(minPlayersRequired: 2);
    }

    public function testCanStartWithSufficientPlayers(): void
    {
        $result = $this->policy->canStart(playersCount: 2, startedAt: null);

        $this->assertTrue($result);
    }

    public function testCannotStartWithInsufficientPlayers(): void
    {
        $result = $this->policy->canStart(playersCount: 1, startedAt: null);

        $this->assertFalse($result);
    }

    public function testCannotStartAlreadyStartedGame(): void
    {
        $result = $this->policy->canStart(playersCount: 2, startedAt: Timestamp::now());

        $this->assertFalse($result);
    }

    public function testValidateStartThrowsExceptionWhenGameAlreadyStarted(): void
    {
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174000');
        
        $this->expectException(GameAlreadyStartedException::class);
        $this->expectExceptionMessage("Game {$gameId} was already started.");

        $this->policy->validateStart($gameId, playersCount: 2, startedAt: Timestamp::now());
    }

    public function testValidateStartThrowsExceptionWhenInsufficientPlayers(): void
    {
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174000');
        
        $this->expectException(InsufficientPlayersException::class);
        $this->expectExceptionMessage('Minimum 2 players required, but only 1 joined.');

        $this->policy->validateStart($gameId, playersCount: 1, startedAt: null);
    }

    public function testValidateStartPassesWithValidConditions(): void
    {
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174000');
        
        // Should not throw any exception
        $this->policy->validateStart($gameId, playersCount: 2, startedAt: null);

        $this->assertTrue(true); // If we reach here, test passed
    }
} 