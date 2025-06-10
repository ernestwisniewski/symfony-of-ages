<?php

namespace Tests\Unit\Domain\Unit\Policy;

use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
use App\Domain\Unit\Exception\InvalidAttackException;
use App\Domain\Unit\Policy\UnitCombatPolicy;
use App\Domain\Unit\ValueObject\Health;
use App\Domain\Unit\ValueObject\UnitId;
use App\Domain\Unit\ValueObject\UnitType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class UnitCombatPolicyTest extends TestCase
{
    private UnitCombatPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new UnitCombatPolicy();
    }

    public function testCanAttackValidTarget(): void
    {
        $attackerId = new UnitId(Uuid::v4()->toRfc4122());
        $attackerPosition = new Position(5, 5);
        $attackerOwner = new PlayerId(Uuid::v4()->toRfc4122());

        $targetId = new UnitId(Uuid::v4()->toRfc4122());
        $targetPosition = new Position(6, 5); // Adjacent position
        $targetOwner = new PlayerId(Uuid::v4()->toRfc4122());
        $targetHealth = new Health(50, 100);

        $result = $this->policy->canAttack(
            $attackerId,
            $attackerPosition,
            $attackerOwner,
            $targetId,
            $targetPosition,
            $targetOwner,
            $targetHealth
        );

        $this->assertTrue($result);
    }

    public function testCannotAttackSelf(): void
    {
        $unitId = new UnitId(Uuid::v4()->toRfc4122());
        $position = new Position(5, 5);
        $owner = new PlayerId(Uuid::v4()->toRfc4122());
        $health = new Health(50, 100);

        $result = $this->policy->canAttack(
            $unitId,
            $position,
            $owner,
            $unitId, // Same unit
            $position,
            $owner,
            $health
        );

        $this->assertFalse($result);
    }

    public function testCannotAttackFriendlyUnit(): void
    {
        $attackerId = new UnitId(Uuid::v4()->toRfc4122());
        $targetId = new UnitId(Uuid::v4()->toRfc4122());
        $owner = new PlayerId(Uuid::v4()->toRfc4122()); // Same owner
        $attackerPosition = new Position(5, 5);
        $targetPosition = new Position(6, 5);
        $targetHealth = new Health(50, 100);

        $result = $this->policy->canAttack(
            $attackerId,
            $attackerPosition,
            $owner,
            $targetId,
            $targetPosition,
            $owner, // Same owner
            $targetHealth
        );

        $this->assertFalse($result);
    }

    public function testCannotAttackDeadUnit(): void
    {
        $attackerId = new UnitId(Uuid::v4()->toRfc4122());
        $attackerPosition = new Position(5, 5);
        $attackerOwner = new PlayerId(Uuid::v4()->toRfc4122());

        $targetId = new UnitId(Uuid::v4()->toRfc4122());
        $targetPosition = new Position(6, 5);
        $targetOwner = new PlayerId(Uuid::v4()->toRfc4122());
        $targetHealth = new Health(0, 100); // Dead unit

        $result = $this->policy->canAttack(
            $attackerId,
            $attackerPosition,
            $attackerOwner,
            $targetId,
            $targetPosition,
            $targetOwner,
            $targetHealth
        );

        $this->assertFalse($result);
    }

    public function testCannotAttackOutOfRange(): void
    {
        $attackerId = new UnitId(Uuid::v4()->toRfc4122());
        $attackerPosition = new Position(5, 5);
        $attackerOwner = new PlayerId(Uuid::v4()->toRfc4122());

        $targetId = new UnitId(Uuid::v4()->toRfc4122());
        $targetPosition = new Position(8, 5); // 3 tiles away (beyond range 1)
        $targetOwner = new PlayerId(Uuid::v4()->toRfc4122());
        $targetHealth = new Health(50, 100);

        $result = $this->policy->canAttack(
            $attackerId,
            $attackerPosition,
            $attackerOwner,
            $targetId,
            $targetPosition,
            $targetOwner,
            $targetHealth
        );

        $this->assertFalse($result);
    }

    public function testValidateAttackPassesForValidConditions(): void
    {
        $attackerId = new UnitId(Uuid::v4()->toRfc4122());
        $attackerPosition = new Position(5, 5);
        $attackerOwner = new PlayerId(Uuid::v4()->toRfc4122());

        $targetId = new UnitId(Uuid::v4()->toRfc4122());
        $targetPosition = new Position(5, 6);
        $targetOwner = new PlayerId(Uuid::v4()->toRfc4122());
        $targetHealth = new Health(50, 100);

        $this->policy->validateAttack(
            $attackerId,
            $attackerPosition,
            $attackerOwner,
            $targetId,
            $targetPosition,
            $targetOwner,
            $targetHealth
        );

        $this->expectNotToPerformAssertions();
    }

    public function testValidateAttackThrowsExceptionForSelfAttack(): void
    {
        $unitId = new UnitId(Uuid::v4()->toRfc4122());
        $position = new Position(5, 5);
        $owner = new PlayerId(Uuid::v4()->toRfc4122());
        $health = new Health(50, 100);

        $this->expectException(InvalidAttackException::class);
        $this->expectExceptionMessage("Unit {$unitId} cannot attack itself.");

        $this->policy->validateAttack(
            $unitId,
            $position,
            $owner,
            $unitId,
            $position,
            $owner,
            $health
        );
    }

    public function testValidateAttackThrowsExceptionForFriendlyFire(): void
    {
        $attackerId = new UnitId(Uuid::v4()->toRfc4122());
        $targetId = new UnitId(Uuid::v4()->toRfc4122());
        $owner = new PlayerId(Uuid::v4()->toRfc4122());
        $attackerPosition = new Position(5, 5);
        $targetPosition = new Position(6, 5);
        $targetHealth = new Health(50, 100);

        $this->expectException(InvalidAttackException::class);
        $this->expectExceptionMessage("Unit {$attackerId} cannot attack friendly unit {$targetId}.");

        $this->policy->validateAttack(
            $attackerId,
            $attackerPosition,
            $owner,
            $targetId,
            $targetPosition,
            $owner,
            $targetHealth
        );
    }

    public function testValidateAttackThrowsExceptionForDeadTarget(): void
    {
        $attackerId = new UnitId(Uuid::v4()->toRfc4122());
        $targetId = new UnitId(Uuid::v4()->toRfc4122());
        $attackerPosition = new Position(5, 5);
        $targetPosition = new Position(6, 5);
        $attackerOwner = new PlayerId(Uuid::v4()->toRfc4122());
        $targetOwner = new PlayerId(Uuid::v4()->toRfc4122());
        $targetHealth = new Health(0, 100);

        $this->expectException(InvalidAttackException::class);
        $this->expectExceptionMessage("Cannot attack unit {$targetId} - target is already dead.");

        $this->policy->validateAttack(
            $attackerId,
            $attackerPosition,
            $attackerOwner,
            $targetId,
            $targetPosition,
            $targetOwner,
            $targetHealth
        );
    }

    public function testValidateAttackThrowsExceptionForOutOfRange(): void
    {
        $attackerId = new UnitId(Uuid::v4()->toRfc4122());
        $targetId = new UnitId(Uuid::v4()->toRfc4122());
        $attackerPosition = new Position(5, 5);
        $targetPosition = new Position(8, 8);
        $attackerOwner = new PlayerId(Uuid::v4()->toRfc4122());
        $targetOwner = new PlayerId(Uuid::v4()->toRfc4122());
        $targetHealth = new Health(50, 100);

        $this->expectException(InvalidAttackException::class);
        $this->expectExceptionMessage("Target at (8, 8) is too far from attacker at (5, 5).");

        $this->policy->validateAttack(
            $attackerId,
            $attackerPosition,
            $attackerOwner,
            $targetId,
            $targetPosition,
            $targetOwner,
            $targetHealth
        );
    }

    #[DataProvider('damageCalculationProvider')]
    public function testCalculateDamage(UnitType $attacker, UnitType $defender, int $expectedDamage): void
    {
        $damage = $this->policy->calculateDamage($attacker, $defender);

        $this->assertEquals($expectedDamage, $damage);
    }

    public static function damageCalculationProvider(): array
    {
        return [
            'warrior_vs_archer' => [UnitType::WARRIOR, UnitType::ARCHER, 7], // 15 - 8 = 7
            'archer_vs_warrior' => [UnitType::ARCHER, UnitType::WARRIOR, 1], // 12 - 12 = 0, minimum 1
            'cavalry_vs_scout' => [UnitType::CAVALRY, UnitType::SCOUT, 12], // 18 - 6 = 12
            'siege_vs_cavalry' => [UnitType::SIEGE_ENGINE, UnitType::CAVALRY, 15], // 25 - 10 = 15
            'scout_vs_siege' => [UnitType::SCOUT, UnitType::SIEGE_ENGINE, 3], // 8 - 5 = 3
        ];
    }

    public function testDamageNeverGoesBelowOne(): void
    {
        // Archer (12 attack) vs Warrior (12 defense) should deal minimum 1 damage
        $damage = $this->policy->calculateDamage(UnitType::ARCHER, UnitType::WARRIOR);

        $this->assertEquals(1, $damage);
    }

    #[DataProvider('adjacentPositionsProvider')]
    public function testAttackRangeIsOneSquare(Position $attacker, Position $target, bool $canAttack): void
    {
        $attackerId = new UnitId(Uuid::v4()->toRfc4122());
        $targetId = new UnitId(Uuid::v4()->toRfc4122());
        $attackerOwner = new PlayerId(Uuid::v4()->toRfc4122());
        $targetOwner = new PlayerId(Uuid::v4()->toRfc4122());
        $targetHealth = new Health(50, 100);

        // Special case for same position - use same unit ID to test self-attack
        if ($attacker->x === $target->x && $attacker->y === $target->y) {
            $targetId = $attackerId; // Same unit = self attack
        }

        $result = $this->policy->canAttack(
            $attackerId,
            $attacker,
            $attackerOwner,
            $targetId,
            $target,
            $targetOwner,
            $targetHealth
        );

        $this->assertEquals($canAttack, $result);
    }

    public static function adjacentPositionsProvider(): array
    {
        return [
            'same_position' => [new Position(5, 5), new Position(5, 5), false], // Same position = self
            'north' => [new Position(5, 5), new Position(5, 4), true],
            'south' => [new Position(5, 5), new Position(5, 6), true],
            'east' => [new Position(5, 5), new Position(6, 5), true],
            'west' => [new Position(5, 5), new Position(4, 5), true],
            'two_squares_north' => [new Position(5, 5), new Position(5, 3), false],
            'two_squares_east' => [new Position(5, 5), new Position(7, 5), false],
            'diagonal' => [new Position(5, 5), new Position(6, 6), false], // Distance 2
        ];
    }
}
