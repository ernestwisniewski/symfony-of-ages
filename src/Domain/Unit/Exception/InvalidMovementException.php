<?php

namespace App\Domain\Unit\Exception;

use App\Domain\City\ValueObject\Position;

final class InvalidMovementException extends UnitException
{
    public static function tooFar(Position $from, Position $to, int $maxRange): self
    {
        $distance = abs($to->x - $from->x) + abs($to->y - $from->y);
        return new self("Cannot move from ({$from->x}, {$from->y}) to ({$to->x}, {$to->y}). Distance {$distance} exceeds maximum range {$maxRange}.");
    }

    public static function positionOccupied(Position $position): self
    {
        return new self("Position ({$position->x}, {$position->y}) is already occupied by another unit.");
    }

    public static function invalidTerrain(Position $position): self
    {
        return new self("Unit cannot move to position ({$position->x}, {$position->y}) due to impassable terrain.");
    }
} 