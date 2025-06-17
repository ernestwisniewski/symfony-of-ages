<?php

namespace App\Domain\Diplomacy\Event;
final readonly class DiplomacyProposed
{
    public function __construct(
        public string $diplomacyId,
        public string $initiatorId,
        public string $targetId,
        public string $gameId,
        public string $agreementType,
        public string $proposedAt
    )
    {
    }
}
