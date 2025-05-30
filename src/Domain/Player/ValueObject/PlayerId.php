<?php

namespace App\Domain\Player\ValueObject;

use InvalidArgumentException;

/**
 * PlayerId value object for strongly typed player identification
 *
 * Encapsulates player ID validation and provides type safety
 * for player identification across the domain.
 */
class PlayerId
{
    private string $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Player ID cannot be empty');
        }

        if (strlen($value) < 3) {
            throw new InvalidArgumentException('Player ID must be at least 3 characters long');
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
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
