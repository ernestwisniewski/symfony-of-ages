<?php

namespace App\Domain\Game\Service;

use App\Domain\Game\Policy\GameStartPolicy;
use App\Domain\Game\Policy\PlayerJoinPolicy;
use App\Domain\Game\Policy\TurnEndPolicy;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;

final readonly class GameManagementService
{
    public function __construct(
        private GameStartPolicy  $gameStartPolicy,
        private PlayerJoinPolicy $playerJoinPolicy,
        private TurnEndPolicy    $turnEndPolicy
    )
    {
    }

    public function validateGameStart(
        GameId     $gameId,
        int        $playersCount,
        ?Timestamp $startedAt
    ): void
    {
        $this->gameStartPolicy->validateStart($gameId, $playersCount, $startedAt);
    }

    public function validatePlayerJoin(
        GameId     $gameId,
        PlayerId   $playerId,
        array      $existingPlayers,
        ?Timestamp $startedAt
    ): void
    {
        $this->playerJoinPolicy->validateJoin($gameId, $playerId, $existingPlayers, $startedAt);
    }

    public function validateTurnEnd(
        GameId     $gameId,
        PlayerId   $playerId,
        PlayerId   $activePlayer,
        ?Timestamp $startedAt
    ): void
    {
        $this->turnEndPolicy->validateEndTurn($gameId, $playerId, $activePlayer, $startedAt);
    }

    public function canGameStart(int $playersCount, ?Timestamp $startedAt): bool
    {
        return $this->gameStartPolicy->canStart($playersCount, $startedAt);
    }

    public function canPlayerJoin(
        PlayerId   $playerId,
        array      $existingPlayers,
        ?Timestamp $startedAt
    ): bool
    {
        return $this->playerJoinPolicy->canJoin($playerId, $existingPlayers, $startedAt);
    }

    public function canEndTurn(
        PlayerId   $playerId,
        PlayerId   $activePlayer,
        ?Timestamp $startedAt
    ): bool
    {
        return $this->turnEndPolicy->canEndTurn($playerId, $activePlayer, $startedAt);
    }
}
