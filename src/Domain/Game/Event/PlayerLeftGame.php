<?php

namespace App\Domain\Game\Event;

use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use DateTimeImmutable;

/**
 * Domain event fired when a player leaves a game
 */
class PlayerLeftGame
{
    public function __construct(
        public readonly GameId            $gameId,
        public readonly PlayerId          $playerId,
        public readonly DateTimeImmutable $occurredAt = new DateTimeImmutable
    )
    {
    }
}
