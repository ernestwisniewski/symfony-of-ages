<?php

namespace App\Domain\Game\Policy;

use App\Domain\Game\Exception\GameNotStartedException;
use App\Domain\Game\Exception\NotPlayerTurnException;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;

final readonly class TurnEndPolicy
{
    public function canEndTurn(
        PlayerId   $playerId,
        PlayerId   $activePlayer,
        ?Timestamp $startedAt
    ): bool
    {
        return $startedAt !== null && $activePlayer->isEqual($playerId);
    }

    public function validateEndTurn(
        GameId     $gameId,
        PlayerId   $playerId,
        PlayerId   $activePlayer,
        ?Timestamp $startedAt
    ): void
    {
        if ($startedAt === null) {
            throw GameNotStartedException::create($gameId);
        }

        if (!$activePlayer->isEqual($playerId)) {
            throw NotPlayerTurnException::create($playerId, $activePlayer);
        }
    }
}
