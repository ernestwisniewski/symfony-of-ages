<?php

namespace App\Domain\Unit;

use App\Application\Unit\Command\AttackUnitCommand;
use App\Application\Unit\Command\CreateUnitCommand;
use App\Application\Unit\Command\MoveUnitCommand;
use App\Domain\City\ValueObject\Position;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Unit\Event\UnitWasAttacked;
use App\Domain\Unit\Event\UnitWasCreated;
use App\Domain\Unit\Event\UnitWasDestroyed;
use App\Domain\Unit\Event\UnitWasMoved;
use App\Domain\Unit\Exception\UnitAlreadyDeadException;
use App\Domain\Unit\Policy\UnitCombatPolicy;
use App\Domain\Unit\Policy\UnitCreationPolicy;
use App\Domain\Unit\Policy\UnitMovementPolicy;
use App\Domain\Unit\ValueObject\Health;
use App\Domain\Unit\ValueObject\UnitId;
use App\Domain\Unit\ValueObject\UnitType;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
class Unit
{
    use WithAggregateVersioning;

    #[Identifier]
    private UnitId $unitId;
    private PlayerId $ownerId;
    private GameId $gameId;
    private UnitType $type;
    private Position $position;
    private Health $health;
    private bool $isDead = false;

    #[CommandHandler]
    public static function create(
        CreateUnitCommand  $command,
        UnitCreationPolicy $creationPolicy
    ): array
    {
        // For unit creation, we would need terrain info and existing units
        // For now, skip validation as it would require additional context

        $maxHealth = $command->type->getMaxHealth();

        return [
            new UnitWasCreated(
                unitId: (string)$command->unitId,
                ownerId: (string)$command->ownerId,
                gameId: (string)$command->gameId,
                type: $command->type->value,
                x: $command->position->x,
                y: $command->position->y,
                currentHealth: $maxHealth,
                maxHealth: $maxHealth,
                createdAt: $command->createdAt->format()
            )
        ];
    }

    #[CommandHandler]
    public function move(
        MoveUnitCommand    $command,
        UnitMovementPolicy $movementPolicy
    ): array
    {
        if ($this->isDead) {
            throw UnitAlreadyDeadException::create($this->unitId);
        }

        $movementPolicy->validateMovement(
            $this->position,
            $command->toPosition,
            $this->type,
            $command->existingUnits
        );

        return [
            new UnitWasMoved(
                unitId: (string)$this->unitId,
                fromX: $this->position->x,
                fromY: $this->position->y,
                toX: $command->toPosition->x,
                toY: $command->toPosition->y,
                movedAt: $command->movedAt->format()
            )
        ];
    }

    #[CommandHandler]
    public function attack(
        AttackUnitCommand $command,
        UnitCombatPolicy  $combatPolicy
    ): array
    {
        if ($this->isDead) {
            throw UnitAlreadyDeadException::create($this->unitId);
        }

        $target = $command->targetUnit;

        $combatPolicy->validateAttack(
            $this->unitId,
            $this->position,
            $this->ownerId,
            $target->unitId,
            $target->position,
            $target->ownerId,
            $target->health
        );

        $damage = $combatPolicy->calculateDamage($this->type, $target->type);
        $newHealth = $target->health->takeDamage($damage);
        $wasDestroyed = $newHealth->isDead();

        $events = [
            new UnitWasAttacked(
                attackerUnitId: (string)$this->unitId,
                defenderUnitId: (string)$target->unitId,
                damage: $damage,
                remainingHealth: $newHealth->current,
                wasDestroyed: $wasDestroyed,
                attackedAt: $command->attackedAt->format()
            )
        ];

        if ($wasDestroyed) {
            $events[] = new UnitWasDestroyed(
                unitId: (string)$target->unitId,
                destroyedAt: $command->attackedAt->format()
            );
        }

        return $events;
    }

    #[EventSourcingHandler]
    public function whenUnitWasCreated(UnitWasCreated $event): void
    {
        $this->unitId = new UnitId($event->unitId);
        $this->ownerId = new PlayerId($event->ownerId);
        $this->gameId = new GameId($event->gameId);
        $this->type = UnitType::from($event->type);
        $this->position = new Position($event->x, $event->y);
        $this->health = new Health($event->currentHealth, $event->maxHealth);
        $this->isDead = false;
    }

    #[EventSourcingHandler]
    public function whenUnitWasMoved(UnitWasMoved $event): void
    {
        $this->position = new Position($event->toX, $event->toY);
    }

    #[EventSourcingHandler]
    public function whenUnitWasAttacked(UnitWasAttacked $event): void
    {
        // Only apply if this unit was the defender
        if ($event->defenderUnitId === (string)$this->unitId) {
            $this->health = new Health($event->remainingHealth, $this->health->maximum);

            if ($event->wasDestroyed) {
                $this->isDead = true;
            }
        }
    }

    #[EventSourcingHandler]
    public function whenUnitWasDestroyed(UnitWasDestroyed $event): void
    {
        if ($event->unitId === (string)$this->unitId) {
            $this->isDead = true;
        }
    }

}
