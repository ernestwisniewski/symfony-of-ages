<?php

namespace App\Domain\Game\ValueObject;

/**
 * GameStatus enum represents different states of a game
 *
 * Defines the lifecycle states of a game from creation to completion
 */
enum GameStatus: string
{
    case WAITING_FOR_PLAYERS = 'waiting_for_players';
    case IN_PROGRESS = 'in_progress';
    case PAUSED = 'paused';
    case ENDED = 'ended';

    /**
     * Gets human-readable status name
     */
    public function getName(): string
    {
        return match ($this) {
            self::WAITING_FOR_PLAYERS => 'Waiting for Players',
            self::IN_PROGRESS => 'In Progress',
            self::PAUSED => 'Paused',
            self::ENDED => 'Ended'
        };
    }

    /**
     * Checks if game is active (can accept actions)
     */
    public function isActive(): bool
    {
        return $this === self::IN_PROGRESS;
    }

    /**
     * Checks if game is finished
     */
    public function isFinished(): bool
    {
        return $this === self::ENDED;
    }

    /**
     * Checks if game can accept new players
     */
    public function canAcceptPlayers(): bool
    {
        return $this === self::WAITING_FOR_PLAYERS;
    }

    /**
     * Gets all possible statuses
     */
    public static function getAllStatuses(): array
    {
        return array_map(fn($status) => $status->value, self::cases());
    }
}
