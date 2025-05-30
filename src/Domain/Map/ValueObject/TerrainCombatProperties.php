<?php

namespace App\Domain\Map\ValueObject;

use App\Domain\Map\Exception\InvalidTerrainDataException;

/**
 * TerrainCombatProperties encapsulates combat-related terrain characteristics
 *
 * Immutable value object that represents the defensive bonuses and tactical
 * advantages provided by different terrain types in combat situations.
 * Uses readonly properties to ensure true immutability.
 */
class TerrainCombatProperties
{
    public readonly int $defenseBonus;

    public function __construct(int $defenseBonus)
    {
        if ($defenseBonus < 0) {
            throw InvalidTerrainDataException::negativeDefenseBonus();
        }

        $this->defenseBonus = $defenseBonus;
    }

    /**
     * Determines if terrain provides defensive advantage (defense >= 3)
     */
    public function providesDefensiveAdvantage(): bool
    {
        return $this->defenseBonus >= 3;
    }

    /**
     * Determines if terrain provides minor defense (defense >= 1 && < 3)
     */
    public function providesMinorDefense(): bool
    {
        return $this->defenseBonus >= 1 && $this->defenseBonus < 3;
    }

    /**
     * Determines if terrain has no defensive value (defense = 0)
     */
    public function hasNoDefensiveValue(): bool
    {
        return $this->defenseBonus === 0;
    }

    /**
     * Determines if terrain is fortified (defense >= 4)
     */
    public function isFortified(): bool
    {
        return $this->defenseBonus >= 4;
    }

    public function toArray(): array
    {
        return [
            'defenseBonus' => $this->defenseBonus,
            'providesDefensiveAdvantage' => $this->providesDefensiveAdvantage(),
            'isFortified' => $this->isFortified(),
            'defensiveLevel' => $this->getDefensiveLevel()
        ];
    }

    /**
     * Gets human-readable defensive level
     */
    private function getDefensiveLevel(): string
    {
        return match ($this->defenseBonus) {
            0 => 'None',
            1 => 'Minor',
            2 => 'Moderate',
            3 => 'Strong',
            4 => 'Fortress Level 1',
            5 => 'Fortress Level 2',
            default => 'Heavily Fortified'
        };
    }
}
