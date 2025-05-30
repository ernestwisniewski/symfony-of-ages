<?php

namespace App\Domain\Map\ValueObject;

/**
 * TerrainCombatProperties represents tactical/combat terrain characteristics
 *
 * Value Object containing defense bonuses and tactical advantages.
 * Used by combat systems and tactical analysis.
 */
readonly class TerrainCombatProperties
{
    public function __construct(
        private int $defenseBonus
    ) {
        if ($defenseBonus < 0) {
            throw new \InvalidArgumentException('Defense bonus cannot be negative');
        }
    }

    public function getDefenseBonus(): int
    {
        return $this->defenseBonus;
    }

    public function providesDefensiveAdvantage(): bool
    {
        return $this->defenseBonus >= 3;
    }

    public function providesMinorDefense(): bool
    {
        return $this->defenseBonus > 0 && $this->defenseBonus < 3;
    }

    public function hasNoDefensiveValue(): bool
    {
        return $this->defenseBonus === 0;
    }

    public function isFortified(): bool
    {
        return $this->defenseBonus >= 4;
    }

    public function toArray(): array
    {
        return [
            'defenseBonus' => $this->defenseBonus,
            'defensiveLevel' => $this->getDefensiveLevel(),
            'tacticalAdvantage' => $this->providesDefensiveAdvantage()
        ];
    }

    private function getDefensiveLevel(): string
    {
        return match (true) {
            $this->hasNoDefensiveValue() => 'none',
            $this->defenseBonus === 1 => 'minimal',
            $this->defenseBonus === 2 => 'light',
            $this->defenseBonus === 3 => 'moderate',
            $this->isFortified() => 'fortified',
            default => 'unknown'
        };
    }
} 