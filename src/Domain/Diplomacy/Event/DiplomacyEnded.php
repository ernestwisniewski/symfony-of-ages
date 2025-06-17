<?php

namespace App\Domain\Diplomacy\Event;
final readonly class DiplomacyEnded
{
    public function __construct(
        public string $diplomacyId,
        public string $endedBy,
        public string $endedAt
    )
    {
    }
}
