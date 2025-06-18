<?php

namespace App\Application\Visibility\Query;

use App\Domain\Game\ValueObject\GameId;

final readonly class GetGameVisibilityQuery
{
    public function __construct(
        public GameId $gameId
    ) {
    }
} 