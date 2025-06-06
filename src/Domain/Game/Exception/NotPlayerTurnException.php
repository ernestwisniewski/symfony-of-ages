<?php

namespace App\Domain\Game\Exception;

use App\Domain\Player\ValueObject\PlayerId;

final class NotPlayerTurnException extends GameException
{
    public static function create(PlayerId $playerId, PlayerId $activePlayer): self
    {
        return new self("It is not player {$playerId}'s turn. Current active player is {$activePlayer}.");
    }
} 