<?php

namespace App\Domain\Game\Event;

use App\Domain\Game\ValueObject\GameId;
use DateTimeImmutable;

/**
 * Domain event fired when a game ends
 */
class GameEnded
{
    public function __construct(
        public readonly GameId            $gameId,
        public readonly DateTimeImmutable $occurredAt = new DateTimeImmutable,
        public readonly ?string           $reason = null
    )
    {
    }
}
