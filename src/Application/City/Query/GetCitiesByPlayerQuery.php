<?php

namespace App\Application\City\Query;

use App\Domain\Player\ValueObject\PlayerId;

final readonly class GetCitiesByPlayerQuery
{
    public function __construct(
        public PlayerId $playerId,
    )
    {
    }
} 