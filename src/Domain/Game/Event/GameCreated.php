<?php

namespace App\Domain\Game\Event;

use App\Domain\Game\ValueObject\GameId;
use DateTimeImmutable;

/**
 * Domain event fired when a new game is created
 */
class GameCreated
{
    public function __construct(
        public readonly GameId            $gameId,
        public readonly string            $gameName,
        public readonly DateTimeImmutable $occurredAt = new DateTimeImmutable
    )
    {
    }
}
