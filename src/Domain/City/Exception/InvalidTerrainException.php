<?php

namespace App\Domain\City\Exception;

use App\Domain\Map\ValueObject\TerrainType;
use App\Domain\Shared\ValueObject\Position;

final class InvalidTerrainException extends CityException
{
    public static function create(Position $position, TerrainType $terrain): self
    {
        return new self("Cannot found city on {$terrain->value} terrain at position ({$position->x}, {$position->y}).");
    }
}
