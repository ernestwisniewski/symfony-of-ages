<?php

namespace App\Application\Game\Command;

use App\Domain\Game\ValueObject\GameId;
use App\Domain\Shared\ValueObject\Timestamp;

final readonly class StartGameCommand
{
    public function __construct(
        public GameId    $gameId,
        public Timestamp $startedAt,
    )
    {
    }
}
