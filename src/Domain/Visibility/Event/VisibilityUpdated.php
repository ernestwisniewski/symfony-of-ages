<?php

namespace App\Domain\Visibility\Event;

use App\Domain\Shared\ValueObject\Position;
use App\Domain\Visibility\ValueObject\VisibilityState;

final readonly class VisibilityUpdated
{
    public function __construct(
        public string $playerId,
        public string $gameId,
        public int $x,
        public int $y,
        public string $state,
        public string $updatedAt
    ) {
    }
} 