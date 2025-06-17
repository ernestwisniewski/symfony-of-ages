<?php

namespace App\Domain\Unit\Event;
final readonly class UnitWasAttacked
{
    public function __construct(
        public string $attackerUnitId,
        public string $defenderUnitId,
        public int    $damage,
        public int    $remainingHealth,
        public bool   $wasDestroyed,
        public string $attackedAt
    )
    {
    }
}
