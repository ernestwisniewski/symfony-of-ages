<?php

namespace App\Domain\Unit\Event;

final readonly class UnitWasCreated
{
    public function __construct(
        public string $unitId,
        public string $ownerId,
        public string $gameId,
        public string $type,
        public int    $x,
        public int    $y,
        public int    $currentHealth,
        public int    $maxHealth,
        public string $createdAt
    )
    {
    }
}
