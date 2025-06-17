<?php

namespace App\Domain\Technology\ValueObject;
enum TechnologyType: string
{
    case AGRICULTURE = 'agriculture';
    case MINING = 'mining';
    case WRITING = 'writing';
    case IRON_WORKING = 'iron_working';
    case MATHEMATICS = 'mathematics';
    case ARCHITECTURE = 'architecture';
    case MILITARY_TACTICS = 'military_tactics';
    case NAVIGATION = 'navigation';
    case PHILOSOPHY = 'philosophy';
    case ENGINEERING = 'engineering';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::AGRICULTURE => 'Agriculture',
            self::MINING => 'Mining',
            self::WRITING => 'Writing',
            self::IRON_WORKING => 'Iron Working',
            self::MATHEMATICS => 'Mathematics',
            self::ARCHITECTURE => 'Architecture',
            self::MILITARY_TACTICS => 'Military Tactics',
            self::NAVIGATION => 'Navigation',
            self::PHILOSOPHY => 'Philosophy',
            self::ENGINEERING => 'Engineering',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::AGRICULTURE => 'Learn to cultivate crops and improve food production',
            self::MINING => 'Extract valuable resources from the earth',
            self::WRITING => 'Develop written language for better communication',
            self::IRON_WORKING => 'Master the art of working with iron',
            self::MATHEMATICS => 'Develop advanced mathematical concepts',
            self::ARCHITECTURE => 'Build more sophisticated structures',
            self::MILITARY_TACTICS => 'Improve military strategy and tactics',
            self::NAVIGATION => 'Navigate the seas and oceans',
            self::PHILOSOPHY => 'Develop deeper understanding of the world',
            self::ENGINEERING => 'Create advanced mechanical devices',
        };
    }

    public function getCost(): int
    {
        return match ($this) {
            self::AGRICULTURE => 10,
            self::MINING => 15,
            self::WRITING => 20,
            self::IRON_WORKING => 30,
            self::MATHEMATICS => 25,
            self::ARCHITECTURE => 35,
            self::MILITARY_TACTICS => 40,
            self::NAVIGATION => 30,
            self::PHILOSOPHY => 45,
            self::ENGINEERING => 50,
        };
    }

    public function getPrerequisites(): array
    {
        return match ($this) {
            self::AGRICULTURE => [],
            self::MINING => [],
            self::WRITING => [self::AGRICULTURE],
            self::IRON_WORKING => [self::MINING],
            self::MATHEMATICS => [self::WRITING],
            self::ARCHITECTURE => [self::MATHEMATICS],
            self::MILITARY_TACTICS => [self::IRON_WORKING],
            self::NAVIGATION => [self::WRITING],
            self::PHILOSOPHY => [self::WRITING, self::MATHEMATICS],
            self::ENGINEERING => [self::MATHEMATICS, self::IRON_WORKING],
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
