<?php

namespace App\Domain\Player\ValueObject;

use App\Domain\Player\Exception\InvalidPlayerDataException;

/**
 * PlayerId value object for strongly typed player identification
 *
 * Immutable value object that encapsulates player ID validation
 * and provides type safety for player identification across the domain.
 * Uses readonly properties to ensure true immutability.
 */
class PlayerId
{
    public readonly string $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw InvalidPlayerDataException::emptyPlayerId();
        }

        if (strlen($value) < 3) {
            throw InvalidPlayerDataException::playerIdTooShort(3);
        }

        $this->value = $value;
    }

    public function equals(PlayerId $other): bool
    {
        return $this->value === $other->value;
    }

    public static function generate(): self
    {
        return new self('player_' . uniqid());
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
