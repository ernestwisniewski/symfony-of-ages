<?php

namespace App\Application\Map\Query;

use App\Domain\Game\ValueObject\GameId;

final readonly class GetMapTilesQuery
{
    public function __construct(public GameId $gameId) {}
}
