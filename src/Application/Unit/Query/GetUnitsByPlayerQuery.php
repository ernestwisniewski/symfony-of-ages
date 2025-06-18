<?php

namespace App\Application\Unit\Query;

use App\Domain\Player\ValueObject\PlayerId;

final readonly class GetUnitsByPlayerQuery
{
    public function __construct(
        public PlayerId $playerId,
    )
    {
    }
} 