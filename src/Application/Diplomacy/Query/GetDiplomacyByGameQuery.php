<?php

namespace App\Application\Diplomacy\Query;

use App\Domain\Game\ValueObject\GameId;

final readonly class GetDiplomacyByGameQuery
{
    public function __construct(
        public GameId $gameId
    )
    {
    }
}
