<?php

namespace App\Domain\Technology\Event;
final readonly class TechnologyWasDiscovered
{
    public function __construct(
        public string $technologyId,
        public string $playerId,
        public string $gameId,
        public string $discoveredAt
    )
    {
    }
}
