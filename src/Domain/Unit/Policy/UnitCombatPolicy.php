<?php

namespace App\Domain\Unit\Policy;

use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
use App\Domain\Shared\ValueObject\ValidationConstants;
use App\Domain\Unit\Exception\InvalidAttackException;
use App\Domain\Unit\ValueObject\Health;
use App\Domain\Unit\ValueObject\UnitId;
use App\Domain\Unit\ValueObject\UnitType;

final readonly class UnitCombatPolicy
{
    private const int ATTACK_RANGE = ValidationConstants::ATTACK_RANGE;

    public function canAttack(
        UnitId   $attackerId,
        Position $attackerPosition,
        PlayerId $attackerOwner,
        UnitId   $targetId,
        Position $targetPosition,
        PlayerId $targetOwner,
        Health   $targetHealth
    ): bool
    {
        return !$attackerId->isEqual($targetId)
            && !$attackerOwner->isEqual($targetOwner)
            && !$targetHealth->isDead()
            && $this->isWithinAttackRange($attackerPosition, $targetPosition);
    }

    public function validateAttack(
        UnitId   $attackerId,
        Position $attackerPosition,
        PlayerId $attackerOwner,
        UnitId   $targetId,
        Position $targetPosition,
        PlayerId $targetOwner,
        Health   $targetHealth
    ): void
    {
        if ($attackerId->isEqual($targetId)) {
            throw InvalidAttackException::cannotAttackSelf($attackerId);
        }

        if ($attackerOwner->isEqual($targetOwner)) {
            throw InvalidAttackException::cannotAttackFriendly($attackerId, $targetId);
        }

        if ($targetHealth->isDead()) {
            throw InvalidAttackException::targetAlreadyDead($targetId);
        }

        if (!$this->isWithinAttackRange($attackerPosition, $targetPosition)) {
            throw InvalidAttackException::targetTooFar($attackerPosition, $targetPosition);
        }
    }

    public function calculateDamage(UnitType $attackerType, UnitType $defenderType): int
    {
        $baseDamage = $attackerType->getAttackPower();
        $defense = $defenderType->getDefensePower();

        return max(ValidationConstants::MIN_DAMAGE, $baseDamage - $defense);
    }

    private function isWithinAttackRange(Position $attacker, Position $target): bool
    {
        $distance = abs($target->x - $attacker->x) + abs($target->y - $attacker->y);
        return $distance <= self::ATTACK_RANGE;
    }
}
