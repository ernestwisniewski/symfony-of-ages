<?php

namespace App\Domain\Visibility\Event;

final readonly class VisibilityRevealed
{
    public function __construct(
        public string $playerId,
        public string $gameId,
        public int $x,
        public int $y,
        public string $revealedAt
    ) {
    }
} 