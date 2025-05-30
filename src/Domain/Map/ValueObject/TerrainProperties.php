<?php

namespace App\Domain\Map\ValueObject;

/**
 * TerrainProperties - pure value object for terrain characteristics
 *
 * Simple aggregate containing all specialized terrain property value objects.
 * Provides unified access to visual, movement, combat, and economic properties.
 * Uses modern PHP 8.4 property hooks for clean API.
 *
 * Contains only basic data access and simple property queries.
 * Complex analysis logic has been moved to TerrainAnalyzer domain service.
 */
class TerrainProperties
{
    public function __construct(
        public readonly TerrainVisualProperties   $visual,
        public readonly TerrainMovementProperties $movement,
        public readonly TerrainCombatProperties   $combat,
        public readonly TerrainEconomicProperties $economic
    )
    {
    }

    /**
     * Quick access properties using modern property hooks
     */
    public string $name {
        get => $this->visual->name;
    }

    public int $color {
        get => $this->visual->color;
    }

    public int $movementCost {
        get => $this->movement->movementCost;
    }

    public bool $isPassable {
        get => $this->movement->isPassable();
    }

    public int $defenseBonus {
        get => $this->combat->defenseBonus;
    }

    public int $resourceYield {
        get => $this->economic->resourceYield;
    }

    public string $hexColor {
        get => $this->visual->getHexColor();
    }

    /**
     * Gets visual properties
     */
    public function visual(): TerrainVisualProperties
    {
        return $this->visual;
    }

    /**
     * Gets movement properties
     */
    public function movement(): TerrainMovementProperties
    {
        return $this->movement;
    }

    /**
     * Gets combat properties
     */
    public function combat(): TerrainCombatProperties
    {
        return $this->combat;
    }

    /**
     * Gets economic properties
     */
    public function economic(): TerrainEconomicProperties
    {
        return $this->economic;
    }

    /**
     * Provides basic array format for API responses
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'color' => $this->color,
            'movementCost' => $this->movementCost,
            'defense' => $this->defenseBonus,
            'resources' => $this->resourceYield
        ];
    }

    /**
     * Returns detailed array with all property categories
     */
    public function toDetailedArray(): array
    {
        return [
            'visual' => $this->visual->toArray(),
            'movement' => $this->movement->toArray(),
            'combat' => $this->combat->toArray(),
            'economic' => $this->economic->toArray(),
            'quick_access' => $this->toArray()
        ];
    }
}
