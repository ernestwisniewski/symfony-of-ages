<?php

namespace App\Application\Game\Query;

use App\Domain\Game\ValueObject\GameId;

final readonly class GetGameViewQuery
{
    public function __construct(
        public GameId $gameId
    )
    {
    }
}
