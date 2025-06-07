<?php

namespace App\Domain\Unit\Event;

final readonly class UnitWasDestroyed
{
    public function __construct(
        public string $unitId,
        public string $destroyedAt
    )
    {
    }
}
