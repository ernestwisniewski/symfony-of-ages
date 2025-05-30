<?php

namespace App\Domain\Game\ValueObject;

use App\Domain\Game\Exception\InvalidGameDataException;

/**
 * GameId value object for game identification
 *
 * Immutable value object that encapsulates game ID logic, validation,
 * and business rules for game identification within the system.
 * Uses readonly properties to ensure true immutability.
 */
class GameId
{
    public readonly string $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw InvalidGameDataException::emptyGameId();
        }

        if (strlen($value) < 3) {
            throw InvalidGameDataException::gameIdTooShort(3);
        }

        $this->value = $value;
    }

    public function equals(GameId $other): bool
    {
        return $this->value === $other->value;
    }

    public static function generate(): self
    {
        return new self(uniqid('game_', true));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
