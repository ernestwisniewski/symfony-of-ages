<?php

namespace App\Application\Visibility\Query;

use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;

final readonly class GetPlayerVisibilityQuery
{
    public function __construct(
        public PlayerId $playerId,
        public GameId $gameId
    ) {
    }
} 