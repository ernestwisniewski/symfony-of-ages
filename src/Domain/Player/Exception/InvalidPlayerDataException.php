<?php

namespace App\Domain\Player\Exception;

/**
 * Exception thrown when player data is invalid or violates domain rules
 *
 * Used when player creation or modification fails due to invalid data
 * such as empty names, invalid positions, or constraint violations.
 */
class InvalidPlayerDataException extends PlayerDomainException
{
    public static function emptyName(): self
    {
        return new self('Player name cannot be empty');
    }

    public static function nameTooLong(int $maxLength): self
    {
        return new self("Player name cannot exceed {$maxLength} characters");
    }

    public static function invalidColor(int $color): self
    {
        return new self("Invalid color value: {$color}. Must be a valid hexadecimal value.");
    }

    public static function invalidMovementPoints(int $current, int $maximum): self
    {
        return new self("Invalid movement points: current={$current}, maximum={$maximum}");
    }

    public static function invalidPosition(int $row, int $col): self
    {
        return new self("Invalid position: row={$row}, col={$col}");
    }
}
