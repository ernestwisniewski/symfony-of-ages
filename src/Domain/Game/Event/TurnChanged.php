<?php

namespace App\Domain\Game\Event;

use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use DateTimeImmutable;

/**
 * Domain event fired when a turn changes to next player
 */
class TurnChanged
{
    public function __construct(
        public readonly GameId            $gameId,
        public readonly PlayerId          $currentPlayerId,
        public readonly int               $turnNumber,
        public readonly DateTimeImmutable $occurredAt = new DateTimeImmutable
    )
    {
    }
}
