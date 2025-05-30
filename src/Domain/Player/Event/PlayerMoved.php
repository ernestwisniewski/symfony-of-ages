<?php

namespace App\Domain\Player\Event;

use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Player\ValueObject\Position;
use DateTimeImmutable;

/**
 * PlayerMoved domain event
 *
 * Represents the fact that a player has moved from one position to another.
 * Can be used for logging, notifications, or triggering other domain logic.
 */
class PlayerMoved
{
    private PlayerId $playerId;
    private Position $fromPosition;
    private Position $toPosition;
    private int $movementCost;
    private DateTimeImmutable $occurredAt;

    public function __construct(
        PlayerId $playerId,
        Position $fromPosition,
        Position $toPosition,
        int      $movementCost
    ) {
        $this->playerId = $playerId;
        $this->fromPosition = $fromPosition;
        $this->toPosition = $toPosition;
        $this->movementCost = $movementCost;
        $this->occurredAt = new DateTimeImmutable();
    }

    public function getPlayerId(): PlayerId
    {
        return $this->playerId;
    }

    public function getFromPosition(): Position
    {
        return $this->fromPosition;
    }

    public function getToPosition(): Position
    {
        return $this->toPosition;
    }

    public function getMovementCost(): int
    {
        return $this->movementCost;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getDistance(): int
    {
        return $this->fromPosition->distanceTo($this->toPosition);
    }
}
