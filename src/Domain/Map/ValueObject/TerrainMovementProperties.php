<?php

namespace App\Domain\Map\ValueObject;

/**
 * TerrainMovementProperties represents movement-related terrain characteristics
 *
 * Value Object containing movement cost and passability information.
 * Used by movement validation systems and pathfinding algorithms.
 */
readonly class TerrainMovementProperties
{
    public function __construct(
        private int $movementCost
    ) {
        if ($movementCost < 0) {
            throw new \InvalidArgumentException('Movement cost cannot be negative');
        }
    }

    public function getMovementCost(): int
    {
        return $this->movementCost;
    }

    public function isPassable(): bool
    {
        return $this->movementCost > 0;
    }

    public function isImpassable(): bool
    {
        return $this->movementCost === 0;
    }

    public function isEasyToTraverse(): bool
    {
        return $this->movementCost === 1;
    }

    public function isDifficultToTraverse(): bool
    {
        return $this->movementCost >= 3;
    }

    public function toArray(): array
    {
        return [
            'movementCost' => $this->movementCost,
            'passable' => $this->isPassable(),
            'difficulty' => $this->getMovementDifficultyLevel()
        ];
    }

    private function getMovementDifficultyLevel(): string
    {
        return match (true) {
            $this->isImpassable() => 'impassable',
            $this->isEasyToTraverse() => 'easy',
            $this->movementCost === 2 => 'moderate',
            $this->isDifficultToTraverse() => 'difficult',
            default => 'unknown'
        };
    }
} 