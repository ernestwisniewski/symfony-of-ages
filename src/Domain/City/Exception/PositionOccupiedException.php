<?php

namespace App\Domain\City\Exception;

use App\Domain\Shared\ValueObject\Position;

final class PositionOccupiedException extends CityException
{
    public static function create(Position $position): self
    {
        return new self("Position ({$position->x}, {$position->y}) is already occupied.");
    }
}
