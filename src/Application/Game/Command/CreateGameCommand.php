<?php

namespace App\Application\Game\Command;

use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameName;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Shared\ValueObject\UserId;

final readonly class CreateGameCommand
{
    public function __construct(
        public GameId    $gameId,
        public PlayerId  $playerId,
        public GameName  $name,
        public UserId    $userId,
        public Timestamp $createdAt
    )
    {
    }
}
