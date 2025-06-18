<?php

namespace App\Domain\Diplomacy\ValueObject;

use App\Domain\Diplomacy\Exception\DiplomacyException;
use App\Domain\Player\ValueObject\PlayerId;

final readonly class PlayerRelation
{
    public function __construct(
        public PlayerId $initiatorId,
        public PlayerId $targetId
    )
    {
        if ($this->initiatorId->isEqual($this->targetId)) {
            throw InvalidPlayerRelationException::selfRelation();
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
        throw InvalidPlayerRelationException::notPartOfRelation();
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

class InvalidPlayerRelationException extends DiplomacyException
{
    public static function selfRelation(): self
    {
        return new self('Player cannot have diplomatic relations with themselves');
    }

    public static function notPartOfRelation(): self
    {
        return new self('Player is not part of this relation');
    }
}
