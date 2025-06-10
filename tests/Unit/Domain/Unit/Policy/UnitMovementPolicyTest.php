<?php

namespace Tests\Unit\Domain\Unit\Policy;

use App\Domain\Shared\ValueObject\Position;
use App\Domain\Unit\Exception\InvalidMovementException;
use App\Domain\Unit\Policy\UnitMovementPolicy;
use App\Domain\Unit\ValueObject\UnitType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UnitMovementPolicyTest extends TestCase
{
    private UnitMovementPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new UnitMovementPolicy();
    }

    public function testCanMoveWithinRange(): void
    {
        $from = new Position(5, 5);
        $to = new Position(6, 5); // 1 tile away
        $unitType = UnitType::WARRIOR; // Movement range: 2
        $existingUnits = [];

        $result = $this->policy->canMove($from, $to, $unitType, $existingUnits);

        $this->assertTrue($result);
    }

    public function testCannotMoveOutsideRange(): void
    {
        $from = new Position(5, 5);
        $to = new Position(8, 5); // 3 tiles away
        $unitType = UnitType::WARRIOR; // Movement range: 2
        $existingUnits = [];

        $result = $this->policy->canMove($from, $to, $unitType, $existingUnits);

        $this->assertFalse($result);
    }

    public function testCannotMoveToOccupiedPosition(): void
    {
        $from = new Position(5, 5);
        $to = new Position(6, 5);
        $unitType = UnitType::WARRIOR;
        $existingUnits = [
            ['x' => 6, 'y' => 5, 'unitId' => 'unit-1']
        ];

        $result = $this->policy->canMove($from, $to, $unitType, $existingUnits);

        $this->assertFalse($result);
    }

    public function testCanMoveToUnoccupiedPosition(): void
    {
        $from = new Position(5, 5);
        $to = new Position(6, 5);
        $unitType = UnitType::WARRIOR;
        $existingUnits = [
            ['x' => 3, 'y' => 3, 'unitId' => 'unit-1'],
            ['x' => 8, 'y' => 8, 'unitId' => 'unit-2']
        ];

        $result = $this->policy->canMove($from, $to, $unitType, $existingUnits);

        $this->assertTrue($result);
    }

    public function testScoutHasHigherMovementRange(): void
    {
        $from = new Position(5, 5);
        $to = new Position(10, 5); // 5 tiles away
        $existingUnits = [];

        $warrior = UnitType::WARRIOR; // Range: 2
        $scout = UnitType::SCOUT; // Range: 5

        $this->assertFalse($this->policy->canMove($from, $to, $warrior, $existingUnits));
        $this->assertTrue($this->policy->canMove($from, $to, $scout, $existingUnits));
    }

    public function testValidateMovementPassesForValidMove(): void
    {
        $from = new Position(5, 5);
        $to = new Position(6, 6); // 2 tiles away (Manhattan distance)
        $unitType = UnitType::WARRIOR; // Range: 2
        $existingUnits = [];

        $this->policy->validateMovement($from, $to, $unitType, $existingUnits);

        $this->expectNotToPerformAssertions();
    }

    public function testValidateMovementThrowsExceptionForTooFar(): void
    {
        $from = new Position(5, 5);
        $to = new Position(8, 8); // 6 tiles away
        $unitType = UnitType::WARRIOR; // Range: 2
        $existingUnits = [];

        $this->expectException(InvalidMovementException::class);
        $this->expectExceptionMessage('Cannot move from (5, 5) to (8, 8). Distance 6 exceeds maximum range 2.');

        $this->policy->validateMovement($from, $to, $unitType, $existingUnits);
    }

    public function testValidateMovementThrowsExceptionForOccupiedPosition(): void
    {
        $from = new Position(5, 5);
        $to = new Position(6, 5);
        $unitType = UnitType::WARRIOR;
        $existingUnits = [
            ['x' => 6, 'y' => 5, 'unitId' => 'unit-1']
        ];

        $this->expectException(InvalidMovementException::class);
        $this->expectExceptionMessage('Position (6, 5) is already occupied by another unit.');

        $this->policy->validateMovement($from, $to, $unitType, $existingUnits);
    }

    #[DataProvider('movementRangeProvider')]
    public function testMovementRangeForDifferentUnitTypes(UnitType $unitType, int $expectedRange): void
    {
        $from = new Position(5, 5);
        $existingUnits = [];

        // Test exact range boundary
        $toExact = new Position(5 + $expectedRange, 5);
        $this->assertTrue($this->policy->canMove($from, $toExact, $unitType, $existingUnits));

        // Test beyond range
        $toBeyond = new Position(5 + $expectedRange + 1, 5);
        $this->assertFalse($this->policy->canMove($from, $toBeyond, $unitType, $existingUnits));
    }

    public static function movementRangeProvider(): array
    {
        return [
            'warrior' => [UnitType::WARRIOR, 2],
            'archer' => [UnitType::ARCHER, 2],
            'cavalry' => [UnitType::CAVALRY, 4],
            'scout' => [UnitType::SCOUT, 5],
            'siege_engine' => [UnitType::SIEGE_ENGINE, 1],
        ];
    }

    public function testManhattanDistanceCalculation(): void
    {
        $from = new Position(5, 5);
        $to = new Position(7, 8); // Distance: |7-5| + |8-5| = 2 + 3 = 5
        $unitType = UnitType::SCOUT; // Range: 5
        $existingUnits = [];

        $this->assertTrue($this->policy->canMove($from, $to, $unitType, $existingUnits));

        $toBeyond = new Position(7, 9); // Distance: 2 + 4 = 6 (beyond range)
        $this->assertFalse($this->policy->canMove($from, $toBeyond, $unitType, $existingUnits));
    }

    public function testCannotMoveToSamePosition(): void
    {
        $position = new Position(5, 5);
        $unitType = UnitType::WARRIOR;
        $existingUnits = [];

        $result = $this->policy->canMove($position, $position, $unitType, $existingUnits);

        $this->assertTrue($result); // Distance 0 is within any range
    }
}
