<?php

namespace App\Domain\Map\Exception;

/**
 * Exception thrown when terrain data is invalid or violates domain rules
 *
 * Used when terrain creation or modification fails due to invalid data
 * such as negative movement costs, invalid properties, or constraint violations.
 */
class InvalidTerrainDataException extends MapDomainException
{
    public static function negativeMovementCost(): self
    {
        return new self('Movement cost cannot be negative');
    }

    public static function negativeDefenseBonus(): self
    {
        return new self('Defense bonus cannot be negative');
    }

    public static function negativeAttackBonus(): self
    {
        return new self('Attack bonus cannot be negative');
    }

    public static function negativeResourceYield(): self
    {
        return new self('Resource yield cannot be negative');
    }

    public static function invalidColorValue(int $color): self
    {
        return new self("Invalid color value: {$color}. Must be a valid hexadecimal value.");
    }

    public static function emptySymbol(): self
    {
        return new self('Terrain symbol cannot be empty');
    }

    public static function symbolTooLong(int $maxLength): self
    {
        return new self("Terrain symbol cannot exceed {$maxLength} characters");
    }
} 