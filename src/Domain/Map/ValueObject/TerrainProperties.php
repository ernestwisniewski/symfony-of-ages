<?php

namespace App\Domain\Map\ValueObject;

/**
 * TerrainProperties aggregates all terrain characteristics
 *
 * Main aggregate containing all specialized terrain property value objects.
 * Provides unified access to visual, movement, combat, and economic properties.
 */
readonly class TerrainProperties
{
    public function __construct(
        private TerrainVisualProperties $visual,
        private TerrainMovementProperties $movement,
        private TerrainCombatProperties $combat,
        private TerrainEconomicProperties $economic
    ) {
    }

    public function visual(): TerrainVisualProperties
    {
        return $this->visual;
    }

    public function movement(): TerrainMovementProperties
    {
        return $this->movement;
    }

    public function combat(): TerrainCombatProperties
    {
        return $this->combat;
    }

    public function economic(): TerrainEconomicProperties
    {
        return $this->economic;
    }

    /**
     * Provides backward compatibility with legacy array format
     */
    public function toLegacyArray(): array
    {
        return [
            'name' => $this->visual->getName(),
            'color' => $this->visual->getColor(),
            'movementCost' => $this->movement->getMovementCost(),
            'defense' => $this->combat->getDefenseBonus(),
            'resources' => $this->economic->getResourceYield()
        ];
    }

    public function toDetailedArray(): array
    {
        return [
            'visual' => $this->visual->toArray(),
            'movement' => $this->movement->toArray(),
            'combat' => $this->combat->toArray(),
            'economic' => $this->economic->toArray(),
            'legacy' => $this->toLegacyArray()
        ];
    }

    /**
     * Quick access methods for common operations
     */
    public function getName(): string
    {
        return $this->visual->getName();
    }

    public function getColor(): int
    {
        return $this->visual->getColor();
    }

    public function getMovementCost(): int
    {
        return $this->movement->getMovementCost();
    }

    public function isPassable(): bool
    {
        return $this->movement->isPassable();
    }

    public function getDefenseBonus(): int
    {
        return $this->combat->getDefenseBonus();
    }

    public function getResourceYield(): int
    {
        return $this->economic->getResourceYield();
    }

    /**
     * Tactical analysis methods
     */
    public function isTacticallyAdvantaged(): bool
    {
        return $this->combat->providesDefensiveAdvantage();
    }

    public function isEconomicallyViable(): bool
    {
        return $this->economic->isResourceRich();
    }

    public function isStrategicallyImportant(): bool
    {
        return $this->isTacticallyAdvantaged() || $this->isEconomicallyViable();
    }
} 