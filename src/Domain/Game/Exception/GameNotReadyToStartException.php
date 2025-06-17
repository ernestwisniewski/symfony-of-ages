<?php

namespace App\Domain\Game\Exception;

use App\Domain\Game\ValueObject\GameId;

final class GameNotReadyToStartException extends GameException
{
    public static function insufficientPlayers(GameId $gameId, int $actualPlayers, int $requiredPlayers): self
    {
        return new self("Game {$gameId} cannot be started. Minimum {$requiredPlayers} players required, but only {$actualPlayers} joined.");
    }
} 