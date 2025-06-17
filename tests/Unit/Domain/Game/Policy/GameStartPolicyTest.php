<?php

namespace App\Tests\Unit\Domain\Game\Policy;

use App\Domain\Game\Exception\GameAlreadyStartedException;
use App\Domain\Game\Exception\GameNotReadyToStartException;
use App\Domain\Game\Game;
use App\Domain\Game\Policy\GameStartPolicy;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\Timestamp;
use App\Domain\Player\ValueObject\PlayerId;
use PHPUnit\Framework\TestCase;

final class GameStartPolicyTest extends TestCase
{
    private GameStartPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new GameStartPolicy(minPlayersRequired: 2);
    }

    public function testCanStartGameWithSufficientPlayers(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('isStarted')->willReturn(false);
        $game->method('getPlayers')->willReturn([
            new PlayerId('123e4567-e89b-12d3-a456-426614174000'),
            new PlayerId('123e4567-e89b-12d3-a456-426614174001')
        ]);
        $game->method('getId')->willReturn(new GameId('123e4567-e89b-12d3-a456-426614174000'));

        $result = $this->policy->canStartGame($game);

        $this->assertTrue($result);
    }

    public function testCannotStartGameWithInsufficientPlayers(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('isStarted')->willReturn(false);
        $game->method('getPlayers')->willReturn([
            new PlayerId('123e4567-e89b-12d3-a456-426614174000')
        ]);
        $game->method('getId')->willReturn(new GameId('123e4567-e89b-12d3-a456-426614174000'));

        $this->expectException(GameNotReadyToStartException::class);

        $this->policy->canStartGame($game);
    }

    public function testCannotStartAlreadyStartedGame(): void
    {
        $game = $this->createMock(Game::class);
        $game->method('isStarted')->willReturn(true);
        $game->method('getId')->willReturn(new GameId('123e4567-e89b-12d3-a456-426614174000'));

        $this->expectException(GameAlreadyStartedException::class);

        $this->policy->canStartGame($game);
    }
}
