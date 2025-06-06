<?php

namespace App\Domain\Map\ValueObject;

enum TerrainType: string
{
    case PLAINS = 'plains';
    case FOREST = 'forest';
    case MOUNTAIN = 'mountain';
    case WATER = 'water';
    case DESERT = 'desert';
    case SWAMP = 'swamp';

    public function isPassable(): bool
    {
        return $this !== self::WATER;
    }

    public static function allValues(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    public static function count(): int
    {
        return count(self::cases());
    }
}
