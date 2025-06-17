<?php

namespace App\Application\Diplomacy\Query;

use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;

final readonly class GetDiplomacyStatusQuery
{
    public function __construct(
        public PlayerId $playerId,
        public GameId   $gameId
    )
    {
    }
}
