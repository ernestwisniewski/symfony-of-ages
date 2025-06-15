<?php

namespace App\Application\Game\Query;

use App\Domain\Game\ValueObject\GameId;

final readonly class GetGamePlayersQuery
{
    public function __construct(public GameId $gameId)
    {
    }
} 