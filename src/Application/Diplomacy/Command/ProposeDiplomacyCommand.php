<?php

namespace App\Application\Diplomacy\Command;

use App\Domain\Diplomacy\ValueObject\AgreementType;
use App\Domain\Diplomacy\ValueObject\DiplomacyId;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;

final readonly class ProposeDiplomacyCommand
{
    public function __construct(
        public DiplomacyId   $diplomacyId,
        public PlayerId      $initiatorId,
        public PlayerId      $targetId,
        public GameId        $gameId,
        public AgreementType $agreementType,
        public Timestamp     $proposedAt
    )
    {
    }
}
