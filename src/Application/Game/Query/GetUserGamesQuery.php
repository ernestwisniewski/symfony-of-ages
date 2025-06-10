<?php

namespace App\Application\Game\Query;

use App\Domain\Shared\ValueObject\UserId;

final readonly class GetUserGamesQuery
{
    public function __construct(
        public UserId $userId
    )
    {
    }
} 