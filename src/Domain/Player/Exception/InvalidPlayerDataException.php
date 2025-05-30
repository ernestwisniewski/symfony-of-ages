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

    public static function negativeRow(): self
    {
        return new self('Row cannot be negative');
    }

    public static function negativeColumn(): self
    {
        return new self('Column cannot be negative');
    }

    public static function missingRowData(): self
    {
        return new self('Row is required');
    }

    public static function missingColumnData(): self
    {
        return new self('Column is required');
    }

    public static function emptyPlayerId(): self
    {
        return new self('Player ID cannot be empty');
    }

    public static function playerIdTooShort(int $minLength): self
    {
        return new self("Player ID must be at least {$minLength} characters long");
    }

    public static function negativeMovementPoints(string $type): self
    {
        return new self("{$type} movement points cannot be negative");
    }

    public static function movementPointsExceedMaximum(): self
    {
        return new self('Current movement points cannot exceed maximum');
    }

    public static function cannotSpendMovementPoints(int $cost, int $available): self
    {
        return new self("Cannot spend {$cost} movement points. Available: {$available}");
    }
}
