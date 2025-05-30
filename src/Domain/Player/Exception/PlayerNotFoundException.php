<?php

namespace App\Domain\Player\Exception;

/**
 * Exception thrown when a player cannot be found
 *
 * Used when attempting to retrieve or operate on a player
 * that doesn't exist in the system.
 */
class PlayerNotFoundException extends PlayerDomainException
{
    public static function byId(string $playerId): self
    {
        return new self("Player with ID '{$playerId}' not found");
    }

    public static function byName(string $playerName): self
    {
        return new self("Player with name '{$playerName}' not found");
    }

    public static function inSession(): self
    {
        return new self('No player found in session. Create a player first.');
    }
}
