<?php

namespace App\Domain\Player\Entity;

use App\Domain\Player\Event\PlayerMoved;
use App\Domain\Player\Exception\InvalidPlayerDataException;
use App\Domain\Player\ValueObject\MovementPoints;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Player\ValueObject\Position;
use App\Domain\Shared\Trait\DomainEventsTrait;

/**
 * Player domain entity - Aggregate Root
 *
 * Represents a player in the game with position, movement capabilities,
 * and identity. Encapsulates player behavior and publishes domain events
 * for movement tracking. Uses modern PHP 8.4 features and proper encapsulation.
 * 
 * Uses DomainEventsTrait for consistent event management.
 */
class Player
{
    use DomainEventsTrait;

    private string $name {
        set {
            if (empty(trim($value))) {
                throw InvalidPlayerDataException::emptyName();
            }
            if (strlen($value) > 50) {
                throw InvalidPlayerDataException::nameTooLong(50);
            }
            $this->name = $value;
        }
    }

    private int $color {
        set {
            if ($value < 0 || $value > 0xFFFFFF) {
                throw InvalidPlayerDataException::invalidColor($value);
            }
            $this->color = $value;
        }
    }

    public function __construct(
        private readonly PlayerId $id,
        private Position $position,
        string $name,
        private MovementPoints $movementPoints,
        int $color = 0xFF6B6B
    ) {
        $this->name = $name;
        $this->color = $color;
        if (!isset($this->movementPoints)) {
            $this->movementPoints = MovementPoints::createFull(3);
        }
    }

    /**
     * Convenience factory for creating player with max movement points
     */
    public static function create(
        PlayerId $id,
        Position $position,
        string $name,
        int $maxMovementPoints = 3,
        int $color = 0xFF6B6B
    ): self {
        return new self(
            $id,
            $position,
            $name,
            MovementPoints::createFull($maxMovementPoints),
            $color
        );
    }

    // =================== GETTERS WITH PROPER ENCAPSULATION ===================

    public function getId(): PlayerId
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

    public function getMovementPoints(): MovementPoints
    {
        return $this->movementPoints;
    }

    // =================== CONVENIENCE PROPERTIES ===================

    public int $currentMovementPoints {
        get => $this->movementPoints->current;
    }

    public int $maxMovementPoints {
        get => $this->movementPoints->maximum;
    }

    public bool $hasMovementPointsRemaining {
        get => $this->movementPoints->hasPointsRemaining();
    }

    public bool $isMovementExhausted {
        get => $this->movementPoints->isEmpty();
    }

    public float $movementEfficiencyPercentage {
        get => $this->maxMovementPoints > 0
            ? ($this->currentMovementPoints / $this->maxMovementPoints) * 100
            : 0.0;
    }

    public array $coordinatesArray {
        get => $this->position->toArray();
    }

    // =================== DOMAIN BEHAVIOR ===================

    /**
     * Attempts to move player to a new position
     *
     * Uses domain logic to validate and execute movement,
     * publishes domain events for external systems.
     *
     * @param Position $newPosition Target position
     * @param int $movementCost Cost of the movement
     * @return bool True if movement was successful
     */
    public function moveTo(Position $newPosition, int $movementCost): bool
    {
        if (!$this->movementPoints->canSpend($movementCost)) {
            return false;
        }

        $previousPosition = $this->position;

        // Execute movement
        $this->position = $newPosition;
        $this->movementPoints = $this->movementPoints->spend($movementCost);

        // Publish domain event
        $this->recordDomainEvent(new PlayerMoved(
            $this->id,
            $previousPosition,
            $newPosition,
            $movementCost
        ));

        return true;
    }

    /**
     * Checks if player can move with specified cost
     */
    public function canMoveTo(int $movementCost): bool
    {
        return $this->movementPoints->canSpend($movementCost);
    }

    /**
     * Starts a new turn - restores movement points
     */
    public function startNewTurn(): void
    {
        $this->movementPoints = $this->movementPoints->restore();
    }

    /**
     * Checks if player can continue their turn
     */
    public function canContinueTurn(): bool
    {
        return $this->movementPoints->hasPointsRemaining();
    }

    /**
     * Changes player name with validation
     */
    public function changeName(string $newName): void
    {
        $this->name = $newName; // Uses the property hook for validation
    }

    /**
     * Changes player color
     */
    public function changeColor(int $newColor): void
    {
        $this->color = $newColor; // Uses the property hook for validation
    }

    // =================== SERIALIZATION ===================

    /**
     * Gets player data for client consumption
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->value,
            'name' => $this->name,
            'position' => $this->position->toArray(),
            'movementPoints' => $this->movementPoints->current,
            'maxMovementPoints' => $this->movementPoints->maximum,
            'color' => $this->color
        ];
    }

    /**
     * Factory method to create player from array data
     */
    public static function fromArray(array $data): self
    {
        $playerId = new PlayerId($data['id']);
        $position = Position::fromArray($data['position']);
        $movementPoints = new MovementPoints(
            $data['movementPoints'] ?? ($data['maxMovementPoints'] ?? 3),
            $data['maxMovementPoints'] ?? 3
        );

        return new self(
            $playerId,
            $position,
            $data['name'],
            $movementPoints,
            $data['color'] ?? 0xFF6B6B
        );
    }
}
