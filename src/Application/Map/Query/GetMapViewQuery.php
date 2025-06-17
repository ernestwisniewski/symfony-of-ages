<?php

namespace App\Application\Map\Query;

use App\Domain\Game\ValueObject\GameId;

final readonly class GetMapViewQuery
{
    public function __construct(public GameId $gameId)
    {
    }
}
