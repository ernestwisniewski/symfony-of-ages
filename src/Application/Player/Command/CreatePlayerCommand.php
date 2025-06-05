<?php

namespace App\Application\Player\Command;

use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;

final readonly class CreatePlayerCommand
{
    public function __construct(
        public PlayerId $playerId,
        public GameId   $gameId
    )
    {
    }
}
