<?php

namespace App\Domain\Game\Exception;

use App\Domain\Game\ValueObject\GameStatus;
use App\Domain\Player\ValueObject\PlayerId;

/**
 * Exception thrown when game data is invalid
 *
 * Used for validation failures within the game domain
 */
class InvalidGameDataException extends GameDomainException
{
    public static function emptyGameId(): self
    {
        return new self("Game ID cannot be empty");
    }

    public static function gameIdTooShort(int $minLength): self
    {
        return new self("Game ID must be at least {$minLength} characters long");
    }

    public static function invalidMinPlayers(int $minPlayers): self
    {
        return new self("Minimum players must be at least 1, got {$minPlayers}");
    }

    public static function invalidMaxPlayers(int $maxPlayers): self
    {
        return new self("Maximum players must be at least 1, got {$maxPlayers}");
    }

    public static function minPlayersExceedsMax(int $minPlayers, int $maxPlayers): self
    {
        return new self("Minimum players ({$minPlayers}) cannot exceed maximum players ({$maxPlayers})");
    }

    public static function invalidTurnTimeLimit(int $timeLimit): self
    {
        return new self("Turn time limit must be positive, got {$timeLimit}");
    }

    public static function invalidMaxTurns(int $maxTurns): self
    {
        return new self("Maximum turns must be positive, got {$maxTurns}");
    }

    public static function emptyGameName(): self
    {
        return new self("Game name cannot be empty");
    }

    public static function gameNameTooLong(int $maxLength): self
    {
        return new self("Game name cannot exceed {$maxLength} characters");
    }

    // New methods for Game entity

    public static function gameCannotStart(GameStatus $status, int $playerCount, int $minPlayers): self
    {
        return new self("Game cannot start. Status: {$status->value}, Players: {$playerCount}, Required: {$minPlayers}");
    }

    public static function gameCannotBePaused(GameStatus $status): self
    {
        return new self("Game cannot be paused. Current status: {$status->value}");
    }

    public static function gameCannotBeResumed(GameStatus $status): self
    {
        return new self("Game cannot be resumed. Current status: {$status->value}");
    }

    public static function gameCannotAcceptPlayers(GameStatus $status): self
    {
        return new self("Game cannot accept players. Current status: {$status->value}");
    }

    public static function playerAlreadyInGame(PlayerId $playerId): self
    {
        return new self("Player {$playerId->value} is already in the game");
    }

    public static function gameIsFull(int $maxPlayers): self
    {
        return new self("Game is full. Maximum players: {$maxPlayers}");
    }

    public static function playerNotInGame(PlayerId $playerId): self
    {
        return new self("Player {$playerId->value} is not in the game");
    }

    public static function gameNotInProgress(GameStatus $status): self
    {
        return new self("Game is not in progress. Current status: {$status->value}");
    }

    public static function noPlayersInGame(): self
    {
        return new self("No players in the game");
    }
} 