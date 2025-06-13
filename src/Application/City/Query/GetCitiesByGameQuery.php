<?php

declare(strict_types=1);

namespace App\Application\City\Query;

use App\Domain\Game\ValueObject\GameId;

final readonly class GetCitiesByGameQuery
{
    public function __construct(
        public GameId $gameId,
    ) {
    }
} 