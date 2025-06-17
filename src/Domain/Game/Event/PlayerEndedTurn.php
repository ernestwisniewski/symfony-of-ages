<?php

namespace App\Domain\Game\Event;
final readonly class PlayerEndedTurn
{
    public function __construct(
        public string $gameId,
        public string $playerId,
        public string $endedAt
    )
    {
    }
}
