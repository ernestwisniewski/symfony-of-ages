<?php

namespace App\Domain\Visibility\Event;

final readonly class VisibilityUpdated
{
    public function __construct(
        public string $playerId,
        public int    $x,
        public int    $y,
        public string $state,
        public string $updatedAt
    )
    {
    }
}
