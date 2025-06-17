<?php

namespace App\Domain\Unit\Event;
final readonly class UnitWasMoved
{
    public function __construct(
        public string $unitId,
        public int    $fromX,
        public int    $fromY,
        public int    $toX,
        public int    $toY,
        public string $movedAt
    )
    {
    }
}
