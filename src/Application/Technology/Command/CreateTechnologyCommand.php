<?php

namespace App\Application\Technology\Command;

use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;

final readonly class CreateTechnologyCommand
{
    public function __construct(
        public PlayerId  $playerId,
        public GameId    $gameId,
        public Timestamp $createdAt,
        public int       $initialSciencePoints = 0,
    )
    {
    }
}
