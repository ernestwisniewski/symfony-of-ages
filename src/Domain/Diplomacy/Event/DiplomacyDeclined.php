<?php

namespace App\Domain\Diplomacy\Event;
final readonly class DiplomacyDeclined
{
    public function __construct(
        public string $diplomacyId,
        public string $declinedBy,
        public string $declinedAt
    )
    {
    }
}
