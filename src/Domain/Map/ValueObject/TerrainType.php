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

    public function getMovementCost(): int
    {
        return match($this) {
            self::PLAINS => 1,
            self::FOREST => 2,
            self::MOUNTAIN => 3,
            self::WATER => 0, // impassable
            self::DESERT => 2,
            self::SWAMP => 3
        };
    }

    public function getDefenseBonus(): int
    {
        return match($this) {
            self::PLAINS => 0,
            self::FOREST => 2,
            self::MOUNTAIN => 4,
            self::WATER => 0,
            self::DESERT => 1,
            self::SWAMP => 1
        };
    }

    public function getResourceYield(): int
    {
        return match($this) {
            self::PLAINS => 2,
            self::FOREST => 3,
            self::MOUNTAIN => 4,
            self::WATER => 1,
            self::DESERT => 1,
            self::SWAMP => 2
        };
    }

    public function getProductionBonus(): int
    {
        return match($this) {
            self::PLAINS => 0,
            self::FOREST => 1,
            self::MOUNTAIN => 2,
            self::WATER => 0,
            self::DESERT => 0,
            self::SWAMP => 0
        };
    }

    public function getFoodBonus(): int
    {
        return match($this) {
            self::PLAINS => 1,
            self::FOREST => 0,
            self::MOUNTAIN => 0,
            self::WATER => 0,
            self::DESERT => 0,
            self::SWAMP => 0
        };
    }

    public function getGoldBonus(): int
    {
        return match($this) {
            self::PLAINS => 0,
            self::FOREST => 0,
            self::MOUNTAIN => 1,
            self::WATER => 0,
            self::DESERT => 0,
            self::SWAMP => 0
        };
    }

    public function getColor(): int
    {
        return match($this) {
            self::PLAINS => 0x90EE90, // light green
            self::FOREST => 0x228B22, // forest green
            self::MOUNTAIN => 0x8B4513, // saddle brown
            self::WATER => 0x4169E1, // royal blue
            self::DESERT => 0xF4A460, // sandy brown
            self::SWAMP => 0x556B2F  // dark olive green
        };
    }

    public function getDisplayName(): string
    {
        return match($this) {
            self::PLAINS => 'Plains',
            self::FOREST => 'Forest',
            self::MOUNTAIN => 'Mountain',
            self::WATER => 'Water',
            self::DESERT => 'Desert',
            self::SWAMP => 'Swamp'
        };
    }

    public function getProperties(): array
    {
        return [
            'name' => $this->getDisplayName(),
            'movementCost' => $this->getMovementCost(),
            'defenseBonus' => $this->getDefenseBonus(),
            'resourceYield' => $this->getResourceYield(),
            'isPassable' => $this->isPassable(),
            'color' => $this->getColor(),
            'productionBonus' => $this->getProductionBonus(),
            'foodBonus' => $this->getFoodBonus(),
            'goldBonus' => $this->getGoldBonus()
        ];
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
