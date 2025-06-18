<?php

namespace App\Application\Visibility\Query;

use App\Domain\Player\ValueObject\PlayerId;

final readonly class GetPlayerVisibilityQuery
{
    public function __construct(
        public PlayerId $playerId
    )
    {
    }
}
