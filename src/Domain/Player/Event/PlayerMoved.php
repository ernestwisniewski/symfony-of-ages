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
 * Uses modern PHP 8.4 asymmetric visibility for cleaner API.
 */
class PlayerMoved
{
    public function __construct(
        public readonly PlayerId          $playerId,
        public readonly Position          $fromPosition,
        public readonly Position          $toPosition,
        public readonly int               $movementCost,
        public readonly DateTimeImmutable $occurredAt = new DateTimeImmutable
    )
    {
    }

    // Computed property using property hooks
    public int $distance {
        get => $this->fromPosition->distanceTo($this->toPosition);
    }
}
