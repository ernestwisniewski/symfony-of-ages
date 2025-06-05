<?php

namespace App\Domain\Game\Event;

final readonly class GameWasStarted
{
    public function __construct(
        public string $gameId,
        public string $startedAt
    )
    {
    }
}
