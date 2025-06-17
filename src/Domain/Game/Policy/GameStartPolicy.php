<?php

namespace App\Domain\Game\Policy;

use App\Domain\Game\Exception\GameAlreadyStartedException;
use App\Domain\Game\Exception\GameNotReadyToStartException;
use App\Domain\Game\Game;
use App\Domain\Shared\ValueObject\ValidationConstants;

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
}
