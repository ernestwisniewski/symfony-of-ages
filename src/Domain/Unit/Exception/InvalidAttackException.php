<?php

namespace App\Domain\Unit\Exception;

use App\Domain\Shared\ValueObject\Position;
use App\Domain\Unit\ValueObject\UnitId;

final class InvalidAttackException extends UnitException
{
    public static function targetTooFar(Position $attacker, Position $target): self
    {
        return new self("Target at ({$target->x}, {$target->y}) is too far from attacker at ({$attacker->x}, {$attacker->y}).");
    }

    public static function cannotAttackSelf(UnitId $unitId): self
    {
        return new self("Unit {$unitId} cannot attack itself.");
    }

    public static function cannotAttackFriendly(UnitId $attackerId, UnitId $targetId): self
    {
        return new self("Unit {$attackerId} cannot attack friendly unit {$targetId}.");
    }

    public static function targetAlreadyDead(UnitId $targetId): self
    {
        return new self("Cannot attack unit {$targetId} - target is already dead.");
    }
}
