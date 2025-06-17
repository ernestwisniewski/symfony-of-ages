<?php

namespace App\Domain\Game\Policy;

use App\Domain\Game\Exception\GameAlreadyStartedException;
use App\Domain\Game\Exception\GameFullException;
use App\Domain\Game\Exception\PlayerAlreadyJoinedException;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Game\Game;
use App\Domain\Shared\ValueObject\ValidationConstants;

final readonly class PlayerJoinPolicy
{
    public function __construct(
        private int $maxPlayersAllowed = ValidationConstants::MAX_PLAYERS_PER_GAME
    )
    {
    }

    public function canJoin(
        PlayerId   $playerId,
        array      $existingPlayers,
        ?Timestamp $startedAt
    ): bool
    {
        return $startedAt === null
            && !$this->hasPlayer($playerId, $existingPlayers)
            && count($existingPlayers) < $this->maxPlayersAllowed;
    }

    public function validateJoin(
        GameId     $gameId,
        PlayerId   $playerId,
        array      $existingPlayers,
        ?Timestamp $startedAt
    ): void
    {
        if ($startedAt !== null) {
            throw GameAlreadyStartedException::create($gameId);
        }

        if ($this->hasPlayer($playerId, $existingPlayers)) {
            throw PlayerAlreadyJoinedException::create($playerId);
        }

        if (count($existingPlayers) >= $this->maxPlayersAllowed) {
            throw GameFullException::create($this->maxPlayersAllowed);
        }
    }

    private function hasPlayer(PlayerId $playerId, array $players): bool
    {
        return array_any($players, fn($player) => $player->isEqual($playerId));
    }

    public function canJoinGame(Game $game): bool
    {
        if (count($game->getPlayers()) >= $this->maxPlayersAllowed) {
            throw GameFullException::create($game->getId(), $this->maxPlayersAllowed);
        }

        return true;
    }
}
