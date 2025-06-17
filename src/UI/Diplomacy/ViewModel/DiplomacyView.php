<?php

namespace App\UI\Diplomacy\ViewModel;
final class DiplomacyView
{
    public function __construct(
        public string  $diplomacyId,
        public string  $initiatorId,
        public string  $targetId,
        public string  $gameId,
        public string  $agreementType,
        public string  $status,
        public string  $proposedAt,
        public ?string $acceptedAt = null,
        public ?string $declinedAt = null,
        public ?string $endedAt = null
    )
    {
    }
}
