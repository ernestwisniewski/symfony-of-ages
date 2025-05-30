<?php

namespace App\Domain\Game\ValueObject;

use App\Domain\Game\Exception\InvalidGameDataException;

/**
 * GameSettings value object for game configuration
 *
 * Immutable value object that encapsulates game settings such as player limits,
 * turn time, and custom rules for game configuration and validation.
 * Uses readonly properties to ensure true immutability.
 */
class GameSettings
{
    public readonly int $minPlayers;
    public readonly int $maxPlayers;
    public readonly bool $autoStart;
    public readonly ?int $turnTimeLimit; // seconds
    public readonly ?int $maxTurns;
    public readonly bool $allowReconnection;
    public readonly array $customRules;

    public function __construct(
        int   $minPlayers,
        int   $maxPlayers,
        bool  $autoStart = true,
        ?int  $turnTimeLimit = null,
        ?int  $maxTurns = null,
        bool  $allowReconnection = true,
        array $customRules = []
    )
    {
        if ($minPlayers < 1) {
            throw InvalidGameDataException::invalidMinPlayers($minPlayers);
        }

        if ($maxPlayers < 1) {
            throw InvalidGameDataException::invalidMaxPlayers($maxPlayers);
        }

        if ($minPlayers > $maxPlayers) {
            throw InvalidGameDataException::minPlayersExceedsMax($minPlayers, $maxPlayers);
        }

        if ($turnTimeLimit !== null && $turnTimeLimit < 1) {
            throw InvalidGameDataException::invalidTurnTimeLimit($turnTimeLimit);
        }

        if ($maxTurns !== null && $maxTurns < 1) {
            throw InvalidGameDataException::invalidMaxTurns($maxTurns);
        }

        $this->minPlayers = $minPlayers;
        $this->maxPlayers = $maxPlayers;
        $this->autoStart = $autoStart;
        $this->turnTimeLimit = $turnTimeLimit;
        $this->maxTurns = $maxTurns;
        $this->allowReconnection = $allowReconnection;
        $this->customRules = $customRules;
    }

    /**
     * Creates default game settings
     */
    public static function createDefault(): self
    {
        return new self(
            minPlayers: 2,
            maxPlayers: 4,
            autoStart: false,
            turnTimeLimit: 300, // 5 minutes
            maxTurns: 100,
            allowReconnection: true
        );
    }

    /**
     * Creates settings for quick play
     */
    public static function createQuickPlay(): self
    {
        return new self(
            minPlayers: 2,
            maxPlayers: 2,
            autoStart: true,
            turnTimeLimit: 60, // 1 minute
            maxTurns: 50
        );
    }

    /**
     * Creates settings for custom game
     */
    public static function createCustom(
        int   $minPlayers,
        int   $maxPlayers,
        bool  $autoStart = false,
        ?int  $turnTimeLimit = null,
        ?int  $maxTurns = null,
        array $customRules = []
    ): self
    {
        return new self(
            $minPlayers,
            $maxPlayers,
            $autoStart,
            $turnTimeLimit,
            $maxTurns,
            true,
            $customRules
        );
    }

    /**
     * Checks if turn time limit is enabled
     */
    public function hasTurnTimeLimit(): bool
    {
        return $this->turnTimeLimit !== null;
    }

    /**
     * Checks if game has maximum turn limit
     */
    public function hasMaxTurns(): bool
    {
        return $this->maxTurns !== null;
    }

    /**
     * Gets turn time limit in minutes
     */
    public function getTurnTimeLimitInMinutes(): ?float
    {
        return $this->turnTimeLimit ? $this->turnTimeLimit / 60 : null;
    }

    /**
     * Checks if a custom rule exists
     */
    public function hasCustomRule(string $ruleName): bool
    {
        return isset($this->customRules[$ruleName]);
    }

    /**
     * Gets a custom rule value
     */
    public function getCustomRule(string $ruleName, mixed $default = null): mixed
    {
        return $this->customRules[$ruleName] ?? $default;
    }

    /**
     * Converts to array for persistence/serialization
     */
    public function toArray(): array
    {
        return [
            'minPlayers' => $this->minPlayers,
            'maxPlayers' => $this->maxPlayers,
            'autoStart' => $this->autoStart,
            'turnTimeLimit' => $this->turnTimeLimit,
            'maxTurns' => $this->maxTurns,
            'allowReconnection' => $this->allowReconnection,
            'customRules' => $this->customRules
        ];
    }

    /**
     * Creates instance from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['minPlayers'],
            $data['maxPlayers'],
            $data['autoStart'] ?? true,
            $data['turnTimeLimit'] ?? null,
            $data['maxTurns'] ?? null,
            $data['allowReconnection'] ?? true,
            $data['customRules'] ?? []
        );
    }
}
