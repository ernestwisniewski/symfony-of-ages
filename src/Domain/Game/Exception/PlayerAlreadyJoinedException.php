<?php

namespace App\Domain\Game\Exception;

use App\Domain\Player\ValueObject\PlayerId;

final class PlayerAlreadyJoinedException extends GameException
{
    public static function create(PlayerId $playerId): self
    {
        return new self("Player {$playerId} has already joined this game.");
    }
}
