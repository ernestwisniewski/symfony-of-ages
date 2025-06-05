<?php

namespace App\Domain\Game\Event;

final readonly class GameWasCreated
{
    public function __construct(
        public string $gameId,
        public string $playerId,
        public string $name,
        public string $createdAt
    )
    {
    }
}
