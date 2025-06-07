<?php

namespace App\Domain\Game\Policy;

use App\Domain\Game\Exception\GameAlreadyStartedException;
use App\Domain\Game\Exception\InsufficientPlayersException;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Shared\ValueObject\Timestamp;

final readonly class GameStartPolicy
{
    public function __construct(
        private int $minPlayersRequired = 2
    )
    {
    }

    public function canStart(int $playersCount, ?Timestamp $startedAt): bool
    {
        return $startedAt === null && $playersCount >= $this->minPlayersRequired;
    }

    public function validateStart(GameId $gameId, int $playersCount, ?Timestamp $startedAt): void
    {
        if ($startedAt !== null) {
            throw GameAlreadyStartedException::create($gameId);
        }

        if ($playersCount < $this->minPlayersRequired) {
            throw InsufficientPlayersException::create($this->minPlayersRequired, $playersCount);
        }
    }
}
