<?php

namespace App\Application\Technology\Query;

use App\Domain\Player\ValueObject\PlayerId;

final readonly class GetTechnologyTreeQuery
{
    public function __construct(
        public PlayerId $playerId
    )
    {
    }
}
