<?php

namespace App\Domain\Game\Exception;

use App\Domain\Game\ValueObject\GameId;

final class GameAlreadyStartedException extends GameException
{
    public static function create(GameId $gameId): self
    {
        return new self("Game {$gameId} was already started.");
    }
} 