<?php

namespace App\Domain\Map\ValueObject;

use App\Domain\Map\Exception\InvalidTerrainDataException;

/**
 * TerrainMovementProperties encapsulates movement-related terrain characteristics
 *
 * Immutable value object that represents the movement cost and passability
 * properties of terrain types for player movement calculations.
 * Uses readonly properties to ensure true immutability.
 */
class TerrainMovementProperties
{
    public readonly int $movementCost;

    public function __construct(int $movementCost)
    {
        if ($movementCost < 0) {
            throw InvalidTerrainDataException::negativeMovementCost();
        }

        $this->movementCost = $movementCost;
    }

    /**
     * Determines if terrain is passable (movement cost > 0)
     */
    public function isPassable(): bool
    {
        return $this->movementCost > 0;
    }

    /**
     * Determines if terrain is impassable (movement cost = 0)
     */
    public function isImpassable(): bool
    {
        return $this->movementCost === 0;
    }

    /**
     * Determines if terrain is easy to traverse (movement cost = 1)
     */
    public function isEasyToTraverse(): bool
    {
        return $this->movementCost === 1;
    }

    /**
     * Determines if terrain is difficult to traverse (movement cost >= 3)
     */
    public function isDifficultToTraverse(): bool
    {
        return $this->movementCost >= 3;
    }

    public function toArray(): array
    {
        return [
            'movementCost' => $this->movementCost,
            'isPassable' => $this->isPassable(),
            'difficultyLevel' => $this->getMovementDifficultyLevel()
        ];
    }

    /**
     * Gets human-readable movement difficulty level
     */
    private function getMovementDifficultyLevel(): string
    {
        return match ($this->movementCost) {
            0 => 'Impassable',
            1 => 'Easy',
            2 => 'Moderate',
            3 => 'Difficult',
            4 => 'Very Difficult',
            default => 'Extremely Difficult'
        };
    }
}
