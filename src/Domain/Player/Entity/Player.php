<?php

namespace App\Domain\Player\Entity;

use App\Domain\Player\Event\PlayerMoved;
use App\Domain\Player\Exception\InvalidPlayerDataException;
use App\Domain\Player\ValueObject\MovementPoints;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Player\ValueObject\Position;

/**
 * Player entity - Aggregate Root for player-related operations
 *
 * Encapsulates player state and business logic following DDD principles.
 * Maintains invariants, publishes domain events, and provides rich behavior.
 */
class Player
{
    private PlayerId $id;
    private Position $position;
    private MovementPoints $movementPoints;
    private string $name;
    private int $color;
    private array $domainEvents = [];

    public function __construct(
        PlayerId $id,
        Position $position,
        string   $name,
        int      $maxMovementPoints = 3,
        int      $color = 0xFF6B6B
    ) {
        $this->validateName($name);
        $this->validateColor($color);

        $this->id = $id;
        $this->position = $position;
        $this->name = $name;
        $this->movementPoints = MovementPoints::createFull($maxMovementPoints);
        $this->color = $color;
    }

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

    public function getMovementPoints(): int
    {
        return $this->movementPoints->getCurrent();
    }

    public function getMaxMovementPoints(): int
    {
        return $this->movementPoints->getMaximum();
    }

    public function getMovementPointsValueObject(): MovementPoints
    {
        return $this->movementPoints;
    }

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

        // Publish domain eventx
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
        $this->validateName($newName);
        $this->name = $newName;
    }

    /**
     * Changes player color
     */
    public function changeColor(int $newColor): void
    {
        $this->validateColor($newColor);
        $this->color = $newColor;
    }

    /**
     * Gets domain events for publishing
     */
    public function getDomainEvents(): array
    {
        return $this->domainEvents;
    }

    /**
     * Clears domain events after publishing
     */
    public function clearDomainEvents(): void
    {
        $this->domainEvents = [];
    }

    /**
     * Gets player data for client consumption
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->getValue(),
            'name' => $this->name,
            'position' => $this->position->toArray(),
            'movementPoints' => $this->movementPoints->getCurrent(),
            'maxMovementPoints' => $this->movementPoints->getMaximum(),
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

        $player = new self(
            $playerId,
            $position,
            $data['name'],
            $data['maxMovementPoints'] ?? 3,
            $data['color'] ?? 0xFF6B6B
        );

        // Restore movement points if different from max
        if (isset($data['movementPoints']) && $data['movementPoints'] !== $data['maxMovementPoints']) {
            $player->movementPoints = new MovementPoints(
                $data['movementPoints'],
                $data['maxMovementPoints'] ?? 3
            );
        }

        return $player;
    }

    private function validateName(string $name): void
    {
        if (empty(trim($name))) {
            throw InvalidPlayerDataException::emptyName();
        }

        if (strlen($name) > 50) {
            throw InvalidPlayerDataException::nameTooLong(50);
        }
    }

    private function validateColor(int $color): void
    {
        if ($color < 0 || $color > 0xFFFFFF) {
            throw InvalidPlayerDataException::invalidColor($color);
        }
    }

    private function recordDomainEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}
