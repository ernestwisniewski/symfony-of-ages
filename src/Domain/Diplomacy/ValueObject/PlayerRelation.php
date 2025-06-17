<?php

namespace App\Domain\Diplomacy\ValueObject;

use App\Domain\Player\ValueObject\PlayerId;
use InvalidArgumentException;

final readonly class PlayerRelation
{
    public function __construct(
        public PlayerId $initiatorId,
        public PlayerId $targetId
    )
    {
        if ($this->initiatorId->isEqual($this->targetId)) {
            throw new InvalidArgumentException('Player cannot have diplomatic relations with themselves');
        }
    }

    public function involves(PlayerId $playerId): bool
    {
        return $this->initiatorId->isEqual($playerId) || $this->targetId->isEqual($playerId);
    }

    public function getOtherPlayer(PlayerId $playerId): PlayerId
    {
        if ($this->initiatorId->isEqual($playerId)) {
            return $this->targetId;
        }
        if ($this->targetId->isEqual($playerId)) {
            return $this->initiatorId;
        }
        throw new InvalidArgumentException('Player is not part of this relation');
    }

    public function isInitiator(PlayerId $playerId): bool
    {
        return $this->initiatorId->isEqual($playerId);
    }

    public function isTarget(PlayerId $playerId): bool
    {
        return $this->targetId->isEqual($playerId);
    }

    public function toArray(): array
    {
        return [
            'initiatorId' => (string)$this->initiatorId,
            'targetId' => (string)$this->targetId
        ];
    }
}
