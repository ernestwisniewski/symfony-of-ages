<?php

namespace App\Application\Player\Command;

use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\UserId;

final readonly class CreatePlayerCommand
{
    public function __construct(
        public PlayerId $playerId,
        public GameId   $gameId,
        public UserId   $userId
    )
    {
    }
}
