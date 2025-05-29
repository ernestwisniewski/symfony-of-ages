<?php

namespace App\Domain\Player\Entity;

use App\Domain\Player\ValueObject\Position;

/**
 * Player entity representing a game player on the hexagonal map
 *
 * Encapsulates player state including current position, movement capabilities,
 * and core game mechanics. Follows domain-driven design principles with
 * business logic contained within the entity.
 */
class Player
{
    private string $id;
    private Position $position;
    private int $movementPoints;
    private int $maxMovementPoints;
    private string $name;
    private int $color;

    public function __construct(
        string $id,
        Position $position,
        string $name,
        int $maxMovementPoints = 3,
        int $color = 0xFF6B6B
    ) {
        $this->id = $id;
        $this->position = $position;
        $this->name = $name;
        $this->maxMovementPoints = $maxMovementPoints;
        $this->movementPoints = $maxMovementPoints;
        $this->color = $color;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getColor(): int
    {
        return $this->color;
    }

    public function getMovementPoints(): int
    {
        return $this->movementPoints;
    }

    public function getMaxMovementPoints(): int
    {
        return $this->maxMovementPoints;
    }

    /**
     * Attempts to move player to a new position
     *
     * @param Position $newPosition Target position
     * @param int $movementCost Cost of the movement
     * @return bool True if movement was successful
     */
    public function moveTo(Position $newPosition, int $movementCost): bool
    {
        if ($this->movementPoints < $movementCost) {
            return false;
        }

        $this->position = $newPosition;
        $this->movementPoints -= $movementCost;
        
        return true;
    }

    /**
     * Checks if player can move to given position with specified cost
     */
    public function canMoveTo(int $movementCost): bool
    {
        return $this->movementPoints >= $movementCost;
    }

    /**
     * Restores movement points to maximum (start of new turn)
     */
    public function startNewTurn(): void
    {
        $this->movementPoints = $this->maxMovementPoints;
    }

    /**
     * Gets player data for client consumption
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position->toArray(),
            'movementPoints' => $this->movementPoints,
            'maxMovementPoints' => $this->maxMovementPoints,
            'color' => $this->color
        ];
    }
} 