<?php

namespace App\Domain\Game\Exception;

use App\Domain\Player\ValueObject\Position;

/**
 * Exception thrown when a movement is not allowed by game rules
 *
 * Used when player attempts invalid movements such as moving to
 * impassable terrain, moving too far, or having insufficient movement points.
 */
class MovementNotAllowedException extends GameDomainException
{
    public static function insufficientMovementPoints(int $required, int $available): self
    {
        return new self("Insufficient movement points. Required: {$required}, Available: {$available}");
    }

    public static function impassableTerrain(string $terrainType): self
    {
        return new self("Cannot move to impassable terrain: {$terrainType}");
    }

    public static function tooFarFromCurrentPosition(Position $from, Position $to): self
    {
        return new self("Cannot move from {$from} to {$to}. Can only move to adjacent hexes.");
    }

    public static function outOfMapBounds(Position $position, int $maxRows, int $maxCols): self
    {
        return new self("Position {$position} is outside map bounds (max: {$maxRows}x{$maxCols})");
    }

    public static function invalidDistance(int $distance): self
    {
        return new self("Invalid movement distance: {$distance}. Can only move to adjacent positions.");
    }
} 