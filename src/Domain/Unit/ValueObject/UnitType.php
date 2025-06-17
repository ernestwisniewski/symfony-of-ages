<?php

namespace App\Domain\Unit\ValueObject;

enum UnitType: string
{
    case WARRIOR = 'warrior';
    case SETTLER = 'settler';
    case ARCHER = 'archer';
    case CAVALRY = 'cavalry';
    case SCOUT = 'scout';
    case SIEGE_ENGINE = 'siege_engine';

    public function getAttackPower(): int
    {
        return match ($this) {
            self::WARRIOR => 15,
            self::SETTLER => 5,
            self::ARCHER => 12,
            self::CAVALRY => 18,
            self::SCOUT => 8,
            self::SIEGE_ENGINE => 25,
        };
    }

    public function getDefensePower(): int
    {
        return match ($this) {
            self::WARRIOR => 12,
            self::SETTLER => 8,
            self::ARCHER => 8,
            self::CAVALRY => 10,
            self::SCOUT => 6,
            self::SIEGE_ENGINE => 5,
        };
    }

    public function getMaxHealth(): int
    {
        return match ($this) {
            self::WARRIOR => 100,
            self::SETTLER => 80,
            self::ARCHER => 80,
            self::CAVALRY => 90,
            self::SCOUT => 60,
            self::SIEGE_ENGINE => 120,
        };
    }

    public function getMovementRange(): int
    {
        return match ($this) {
            self::WARRIOR => 2,
            self::SETTLER => 2,
            self::ARCHER => 2,
            self::CAVALRY => 4,
            self::SCOUT => 5,
            self::SIEGE_ENGINE => 1,
        };
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