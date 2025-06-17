<?php

namespace App\Application\Diplomacy\Command;

use App\Domain\Diplomacy\ValueObject\DiplomacyId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;

final readonly class DeclineDiplomacyCommand
{
    public function __construct(
        public DiplomacyId $diplomacyId,
        public PlayerId    $declinedBy,
        public Timestamp   $declinedAt
    )
    {
    }
}
