<?php

namespace App\Domain\Player\Event;
final readonly class PlayerWasCreated
{
    public function __construct(
        public string $playerId,
        public string $gameId,
        public int    $userId
    )
    {
    }
}
