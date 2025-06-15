<?php

namespace App\Application\Player\Query;

use App\Domain\Game\ValueObject\GameId;

final readonly class GetUserIdsByGameQuery
{
    public function __construct(
        public GameId $gameId
    ) {
    }
} 