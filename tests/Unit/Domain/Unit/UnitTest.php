<?php

namespace App\Tests\Unit\Domain\Unit;

use App\Application\Unit\Command\AttackUnitCommand;
use App\Application\Unit\Command\CreateUnitCommand;
use App\Application\Unit\Command\MoveUnitCommand;
use App\Application\Unit\DTO\TargetUnitDto;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Unit\Event\UnitWasAttacked;
use App\Domain\Unit\Event\UnitWasCreated;
use App\Domain\Unit\Event\UnitWasDestroyed;
use App\Domain\Unit\Event\UnitWasMoved;
use App\Domain\Unit\Exception\UnitAlreadyDeadException;
use App\Domain\Unit\Policy\UnitCombatPolicy;
use App\Domain\Unit\Policy\UnitCreationPolicy;
use App\Domain\Unit\Policy\UnitMovementPolicy;
use App\Domain\Unit\Unit;
use App\Domain\Unit\ValueObject\Health;
use App\Domain\Unit\ValueObject\UnitId;
use App\Domain\Unit\ValueObject\UnitType;
use Ecotone\Lite\EcotoneLite;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class UnitTest extends TestCase
{
    private function getTestSupport()
    {
        return EcotoneLite::bootstrapFlowTesting([
            Unit::class,
        ], [
            new UnitCreationPolicy(),
            new UnitMovementPolicy(),
            new UnitCombatPolicy(),
        ]);
    }

    public function testCreatesUnitAndEmitsEvent(): void
    {
        // Given
        $unitId = Uuid::v4()->toRfc4122();
        $ownerId = Uuid::v4()->toRfc4122();
        $gameId = Uuid::v4()->toRfc4122();
        $type = UnitType::WARRIOR;
        $position = new Position(5, 5);
        $createdAt = Timestamp::now();

        $command = new CreateUnitCommand(
            new UnitId($unitId),
            new PlayerId($ownerId),
            $type,
            $position,
            $createdAt
        );

        // When
        $testSupport = $this->getTestSupport();
        $recordedEvents = $testSupport
            ->sendCommand($command)
            ->getRecordedEvents();

        // Then
        $this->assertEquals([
            new UnitWasCreated(
                unitId: $unitId,
                ownerId: $ownerId,
                type: $type->value,
                x: $position->x,
                y: $position->y,
                currentHealth: $type->getMaxHealth(),
                maxHealth: $type->getMaxHealth(),
                createdAt: $createdAt->format()
            )
        ], $recordedEvents);
    }

    public function testMovesUnitToValidPosition(): void
    {
        // Given
        $unitId = Uuid::v4()->toRfc4122();
        $ownerId = Uuid::v4()->toRfc4122();
        $type = UnitType::WARRIOR;
        $fromPosition = new Position(5, 5);
        $toPosition = new Position(6, 5);
        $createdAt = Timestamp::now();
        $movedAt = Timestamp::now();

        $existingUnits = [];

        // When
        $testSupport = $this->getTestSupport();

        $testSupport
            ->withEventsFor($unitId, Unit::class, [
                new UnitWasCreated(
                    $unitId,
                    $ownerId,
                    $type->value,
                    $fromPosition->x,
                    $fromPosition->y,
                    $type->getMaxHealth(),
                    $type->getMaxHealth(),
                    $createdAt->format()
                ),
            ])
            ->sendCommand(new MoveUnitCommand(
                new UnitId($unitId),
                $toPosition,
                $existingUnits,
                $movedAt
            ));

        // Then
        $this->assertEquals([
            new UnitWasMoved(
                unitId: $unitId,
                ownerId: $ownerId,
                fromX: $fromPosition->x,
                fromY: $fromPosition->y,
                toX: $toPosition->x,
                toY: $toPosition->y,
                movedAt: $movedAt->format()
            )
        ], $testSupport->getRecordedEvents());
    }

    public function testThrowsExceptionWhenDeadUnitTriesToMove(): void
    {
        // Given
        $unitId = Uuid::v4()->toRfc4122();
        $ownerId = Uuid::v4()->toRfc4122();
        $type = UnitType::WARRIOR;
        $fromPosition = new Position(5, 5);
        $toPosition = new Position(6, 5);
        $createdAt = Timestamp::now();
        $movedAt = Timestamp::now();

        $existingUnits = [];

        $testSupport = $this->getTestSupport();

        $testSupport
            ->withEventsFor($unitId, Unit::class, [
                new UnitWasCreated(
                    $unitId,
                    $ownerId,
                    $type->value,
                    $fromPosition->x,
                    $fromPosition->y,
                    $type->getMaxHealth(),
                    $type->getMaxHealth(),
                    $createdAt->format()
                ),
                new UnitWasDestroyed(
                    $unitId,
                    Timestamp::now()->format()
                )
            ]);

        // When/Then
        $this->expectException(UnitAlreadyDeadException::class);
        $this->expectExceptionMessage("Unit {$unitId} is already dead and cannot perform actions.");

        $testSupport->sendCommand(new MoveUnitCommand(
            new UnitId($unitId),
            $toPosition,
            $existingUnits,
            $movedAt
        ));
    }

    public function testAttacksEnemyUnit(): void
    {
        // Given
        $attackerId = Uuid::v4()->toRfc4122();
        $defenderId = Uuid::v4()->toRfc4122();
        $attackerOwnerId = Uuid::v4()->toRfc4122();
        $defenderOwnerId = Uuid::v4()->toRfc4122();
        $attackerType = UnitType::WARRIOR;
        $defenderType = UnitType::ARCHER;
        $attackerPosition = new Position(5, 5);
        $defenderPosition = new Position(6, 5); // Adjacent
        $createdAt = Timestamp::now();
        $attackedAt = Timestamp::now();

        $defenderDto = new TargetUnitDto(
            unitId: new UnitId($defenderId),
            ownerId: new PlayerId($defenderOwnerId),
            position: $defenderPosition,
            type: $defenderType,
            health: new Health($defenderType->getMaxHealth(), $defenderType->getMaxHealth())
        );

        // When
        $testSupport = $this->getTestSupport();

        $testSupport
            ->withEventsFor($attackerId, Unit::class, [
                new UnitWasCreated(
                    $attackerId,
                    $attackerOwnerId,
                    $attackerType->value,
                    $attackerPosition->x,
                    $attackerPosition->y,
                    $attackerType->getMaxHealth(),
                    $attackerType->getMaxHealth(),
                    $createdAt->format()
                ),
            ]);

        $testSupport->sendCommand(new AttackUnitCommand(
            new UnitId($attackerId),
            $defenderDto,
            $attackedAt
        ));

        // Then
        $expectedDamage = max(1, $attackerType->getAttackPower() - $defenderType->getDefensePower());
        $remainingHealth = $defenderType->getMaxHealth() - $expectedDamage;
        $wasDestroyed = $remainingHealth <= 0;

        $expectedEvents = [
            new UnitWasAttacked(
                attackerUnitId: $attackerId,
                defenderUnitId: $defenderId,
                damage: $expectedDamage,
                remainingHealth: max(0, $remainingHealth),
                wasDestroyed: $wasDestroyed,
                attackedAt: $attackedAt->format()
            )
        ];

        if ($wasDestroyed) {
            $expectedEvents[] = new UnitWasDestroyed(
                unitId: $defenderId,
                destroyedAt: $attackedAt->format()
            );
        }

        $this->assertEquals($expectedEvents, $testSupport->getRecordedEvents());
    }

    public function testThrowsExceptionWhenDeadUnitTriesToAttack(): void
    {
        // Given
        $attackerId = Uuid::v4()->toRfc4122();
        $defenderId = Uuid::v4()->toRfc4122();
        $attackerOwnerId = Uuid::v4()->toRfc4122();
        $defenderOwnerId = Uuid::v4()->toRfc4122();
        $attackerType = UnitType::WARRIOR;
        $defenderType = UnitType::ARCHER;
        $attackerPosition = new Position(5, 5);
        $defenderPosition = new Position(6, 5);
        $createdAt = Timestamp::now();
        $attackedAt = Timestamp::now();

        $defenderDto = new TargetUnitDto(
            unitId: new UnitId($defenderId),
            ownerId: new PlayerId($defenderOwnerId),
            position: $defenderPosition,
            type: $defenderType,
            health: new Health($defenderType->getMaxHealth(), $defenderType->getMaxHealth())
        );

        $testSupport = $this->getTestSupport();

        $testSupport
            ->withEventsFor($attackerId, Unit::class, [
                new UnitWasCreated(
                    $attackerId,
                    $attackerOwnerId,
                    $attackerType->value,
                    $attackerPosition->x,
                    $attackerPosition->y,
                    $attackerType->getMaxHealth(),
                    $attackerType->getMaxHealth(),
                    $createdAt->format()
                ),
                new UnitWasDestroyed(
                    $attackerId,
                    Timestamp::now()->format()
                )
            ]);

        // When/Then
        $this->expectException(UnitAlreadyDeadException::class);
        $this->expectExceptionMessage("Unit {$attackerId} is already dead and cannot perform actions.");

        $testSupport->sendCommand(new AttackUnitCommand(
            new UnitId($attackerId),
            $defenderDto,
            $attackedAt
        ));
    }

    public function testAppliesUnitWasCreatedEvent(): void
    {
        // Given
        $unitId = Uuid::v4()->toRfc4122();
        $ownerId = Uuid::v4()->toRfc4122();
        $type = UnitType::SCOUT;
        $position = new Position(3, 7);
        $createdAt = Timestamp::now();

        $event = new UnitWasCreated(
            $unitId,
            $ownerId,
            $type->value,
            $position->x,
            $position->y,
            $type->getMaxHealth(),
            $type->getMaxHealth(),
            $createdAt->format()
        );

        // When
        $testSupport = $this->getTestSupport();
        $testSupport->withEventsFor($unitId, Unit::class, [$event]);

        // Then - Just verify no exception is thrown and unit is properly initialized
        // by checking if it can perform an action
        $moveCommand = new MoveUnitCommand(
            new UnitId($unitId),
            new Position(4, 7), // Valid move for scout
            [],
            Timestamp::now()
        );

        $recordedEvents = $testSupport
            ->sendCommand($moveCommand)
            ->getRecordedEvents();

        $this->assertCount(1, $recordedEvents);
        $this->assertInstanceOf(UnitWasMoved::class, $recordedEvents[0]);
    }

    public function testAppliesUnitWasMovedEvent(): void
    {
        // Given
        $unitId = Uuid::v4()->toRfc4122();
        $ownerId = Uuid::v4()->toRfc4122();
        $type = UnitType::CAVALRY;
        $initialPosition = new Position(5, 5);
        $newPosition = new Position(7, 6);
        $createdAt = Timestamp::now();
        $movedAt = Timestamp::now();

        $testSupport = $this->getTestSupport();

        $testSupport
            ->withEventsFor($unitId, Unit::class, [
                new UnitWasCreated(
                    $unitId,
                    $ownerId,
                    $type->value,
                    $initialPosition->x,
                    $initialPosition->y,
                    $type->getMaxHealth(),
                    $type->getMaxHealth(),
                    $createdAt->format()
                ),
                new UnitWasMoved(
                    $unitId,
                    $ownerId,
                    $initialPosition->x,
                    $initialPosition->y,
                    $newPosition->x,
                    $newPosition->y,
                    $movedAt->format()
                )
            ]);

        // When - Try to move from the new position to verify the position was updated
        $finalPosition = new Position(8, 6);
        $recordedEvents = $testSupport
            ->sendCommand(new MoveUnitCommand(
                new UnitId($unitId),
                $finalPosition,
                [],
                Timestamp::now()
            ))
            ->getRecordedEvents();

        // Then
        $this->assertCount(1, $recordedEvents);
        $moveEvent = $recordedEvents[0];
        $this->assertInstanceOf(UnitWasMoved::class, $moveEvent);
        $this->assertEquals($newPosition->x, $moveEvent->fromX);
        $this->assertEquals($newPosition->y, $moveEvent->fromY);
    }

    public function testAppliesUnitWasAttackedEventAsDefender(): void
    {
        // Given
        $unitId = Uuid::v4()->toRfc4122();
        $attackerId = Uuid::v4()->toRfc4122();
        $ownerId = Uuid::v4()->toRfc4122();
        $type = UnitType::WARRIOR;
        $position = new Position(5, 5);
        $createdAt = Timestamp::now();
        $attackedAt = Timestamp::now();

        $damage = 25;
        $remainingHealth = $type->getMaxHealth() - $damage;

        $testSupport = $this->getTestSupport();

        $testSupport
            ->withEventsFor($unitId, Unit::class, [
                new UnitWasCreated(
                    $unitId,
                    $ownerId,
                    $type->value,
                    $position->x,
                    $position->y,
                    $type->getMaxHealth(),
                    $type->getMaxHealth(),
                    $createdAt->format()
                ),
                new UnitWasAttacked(
                    $attackerId,
                    $unitId, // This unit is the defender
                    $damage,
                    $remainingHealth,
                    false,
                    $attackedAt->format()
                )
            ]);

        // When - Verify the unit can still act (not dead) but has reduced health
        // This indirectly tests that the health was updated
        $recordedEvents = $testSupport
            ->sendCommand(new MoveUnitCommand(
                new UnitId($unitId),
                new Position(6, 5),
                [],
                Timestamp::now()
            ))
            ->getRecordedEvents();

        // Then - Unit should still be able to move
        $this->assertCount(1, $recordedEvents);
        $this->assertInstanceOf(UnitWasMoved::class, $recordedEvents[0]);
    }

    public function testAppliesUnitWasDestroyedEvent(): void
    {
        // Given
        $unitId = Uuid::v4()->toRfc4122();
        $ownerId = Uuid::v4()->toRfc4122();
        $type = UnitType::WARRIOR;
        $position = new Position(5, 5);
        $createdAt = Timestamp::now();
        $destroyedAt = Timestamp::now();

        $testSupport = $this->getTestSupport();

        $testSupport
            ->withEventsFor($unitId, Unit::class, [
                new UnitWasCreated(
                    $unitId,
                    $ownerId,
                    $type->value,
                    $position->x,
                    $position->y,
                    $type->getMaxHealth(),
                    $type->getMaxHealth(),
                    $createdAt->format()
                ),
                new UnitWasDestroyed(
                    $unitId,
                    $destroyedAt->format()
                )
            ]);

        // When/Then - Unit should not be able to move after being destroyed
        $this->expectException(UnitAlreadyDeadException::class);

        $testSupport->sendCommand(new MoveUnitCommand(
            new UnitId($unitId),
            new Position(6, 5),
            [],
            Timestamp::now()
        ));
    }

    public function testUnitIgnoresAttackEventWhenNotTheDefender(): void
    {
        // Given
        $unitId = Uuid::v4()->toRfc4122();
        $otherUnitId = Uuid::v4()->toRfc4122();
        $attackerId = Uuid::v4()->toRfc4122();
        $ownerId = Uuid::v4()->toRfc4122();
        $type = UnitType::WARRIOR;
        $position = new Position(5, 5);
        $createdAt = Timestamp::now();
        $attackedAt = Timestamp::now();

        $testSupport = $this->getTestSupport();

        $testSupport
            ->withEventsFor($unitId, Unit::class, [
                new UnitWasCreated(
                    $unitId,
                    $ownerId,
                    $type->value,
                    $position->x,
                    $position->y,
                    $type->getMaxHealth(),
                    $type->getMaxHealth(),
                    $createdAt->format()
                ),
                new UnitWasAttacked(
                    $attackerId,
                    $otherUnitId, // Different unit is the defender
                    50,
                    30,
                    false,
                    $attackedAt->format()
                )
            ]);

        // When - Unit should still be able to act normally (event was ignored)
        $recordedEvents = $testSupport
            ->sendCommand(new MoveUnitCommand(
                new UnitId($unitId),
                new Position(6, 5),
                [],
                Timestamp::now()
            ))
            ->getRecordedEvents();

        // Then
        $this->assertCount(1, $recordedEvents);
        $this->assertInstanceOf(UnitWasMoved::class, $recordedEvents[0]);
    }

    public function testUnitIgnoresDestroyedEventWhenNotTheTarget(): void
    {
        // Given
        $unitId = Uuid::v4()->toRfc4122();
        $otherUnitId = Uuid::v4()->toRfc4122();
        $ownerId = Uuid::v4()->toRfc4122();
        $type = UnitType::WARRIOR;
        $position = new Position(5, 5);
        $createdAt = Timestamp::now();
        $destroyedAt = Timestamp::now();

        $testSupport = $this->getTestSupport();

        $testSupport
            ->withEventsFor($unitId, Unit::class, [
                new UnitWasCreated(
                    $unitId,
                    $ownerId,
                    $type->value,
                    $position->x,
                    $position->y,
                    $type->getMaxHealth(),
                    $type->getMaxHealth(),
                    $createdAt->format()
                ),
                new UnitWasDestroyed(
                    $otherUnitId, // Different unit is destroyed
                    $destroyedAt->format()
                )
            ]);

        // When - Unit should still be able to act normally (event was ignored)
        $recordedEvents = $testSupport
            ->sendCommand(new MoveUnitCommand(
                new UnitId($unitId),
                new Position(6, 5),
                [],
                Timestamp::now()
            ))
            ->getRecordedEvents();

        // Then
        $this->assertCount(1, $recordedEvents);
        $this->assertInstanceOf(UnitWasMoved::class, $recordedEvents[0]);
    }
}
