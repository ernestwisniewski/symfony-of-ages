<?php

namespace App\Domain\Game\Event;

final readonly class PlayerWasJoined
{
    public function __construct(
        public string $gameId,
        public string $playerId,
        public string $joinedAt
    )
    {
    }
}
