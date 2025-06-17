<?php

namespace App\Domain\Game\Policy;

use App\Domain\Game\Exception\GameAlreadyStartedException;
use App\Domain\Game\Exception\GameNotReadyToStartException;
use App\Domain\Game\Game;
use App\Domain\Shared\ValueObject\ValidationConstants;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Shared\ValueObject\Timestamp;

final readonly class GameStartPolicy
{
    public function __construct(
        private int $minPlayersRequired = ValidationConstants::MIN_PLAYERS_PER_GAME
    )
    {
    }

    public function canStartGame(Game $game): bool
    {
        if ($game->isStarted()) {
            throw GameAlreadyStartedException::create($game->getId());
        }

        if (count($game->getPlayers()) < $this->minPlayersRequired) {
            throw GameNotReadyToStartException::insufficientPlayers(
                $game->getId(),
                count($game->getPlayers()),
                $this->minPlayersRequired
            );
        }

        return true;
    }

    public function validateStart(
        GameId $gameId,
        int $playersCount,
        ?Timestamp $startedAt
    ): void
    {
        if ($startedAt !== null) {
            throw GameAlreadyStartedException::create($gameId);
        }
        if ($playersCount < $this->minPlayersRequired) {
            throw GameNotReadyToStartException::insufficientPlayers($gameId, $playersCount, $this->minPlayersRequired);
        }
    }
}
