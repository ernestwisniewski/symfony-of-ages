<?php

namespace App\Domain\Diplomacy\Event;
final readonly class DiplomacyAccepted
{
    public function __construct(
        public string $diplomacyId,
        public string $acceptedBy,
        public string $acceptedAt
    )
    {
    }
}
