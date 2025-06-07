<?php

namespace Tests\Unit\Domain\Unit\Policy;

use App\Domain\City\ValueObject\Position;
use App\Domain\Map\ValueObject\TerrainType;
use App\Domain\Unit\Exception\InvalidMovementException;
use App\Domain\Unit\Policy\UnitCreationPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UnitCreationPolicyTest extends TestCase
{
    private UnitCreationPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new UnitCreationPolicy();
    }

    public function testCanCreateUnitOnPassableTerrain(): void
    {
        $position = new Position(5, 5);
        $terrain = TerrainType::PLAINS;
        $existingUnits = [];

        $result = $this->policy->canCreateUnit($position, $terrain, $existingUnits);

        $this->assertTrue($result);
    }

    public function testCannotCreateUnitOnWaterTerrain(): void
    {
        $position = new Position(5, 5);
        $terrain = TerrainType::WATER;
        $existingUnits = [];

        $result = $this->policy->canCreateUnit($position, $terrain, $existingUnits);

        $this->assertFalse($result);
    }

    public function testCannotCreateUnitOnOccupiedPosition(): void
    {
        $position = new Position(5, 5);
        $terrain = TerrainType::PLAINS;
        $existingUnits = [
            ['x' => 5, 'y' => 5, 'unitId' => 'unit-1']
        ];

        $result = $this->policy->canCreateUnit($position, $terrain, $existingUnits);

        $this->assertFalse($result);
    }

    public function testCanCreateUnitOnUnoccupiedPosition(): void
    {
        $position = new Position(5, 5);
        $terrain = TerrainType::PLAINS;
        $existingUnits = [
            ['x' => 3, 'y' => 3, 'unitId' => 'unit-1'],
            ['x' => 7, 'y' => 7, 'unitId' => 'unit-2']
        ];

        $result = $this->policy->canCreateUnit($position, $terrain, $existingUnits);

        $this->assertTrue($result);
    }

    public function testValidateUnitCreationPassesForValidConditions(): void
    {
        $position = new Position(5, 5);
        $terrain = TerrainType::FOREST;
        $existingUnits = [];

        $this->policy->validateUnitCreation($position, $terrain, $existingUnits);

        $this->expectNotToPerformAssertions();
    }

    public function testValidateUnitCreationThrowsExceptionForImpassableTerrain(): void
    {
        $position = new Position(5, 5);
        $terrain = TerrainType::WATER;
        $existingUnits = [];

        $this->expectException(InvalidMovementException::class);
        $this->expectExceptionMessage('Unit cannot move to position (5, 5) due to impassable terrain.');

        $this->policy->validateUnitCreation($position, $terrain, $existingUnits);
    }

    public function testValidateUnitCreationThrowsExceptionForOccupiedPosition(): void
    {
        $position = new Position(5, 5);
        $terrain = TerrainType::PLAINS;
        $existingUnits = [
            ['x' => 5, 'y' => 5, 'unitId' => 'unit-1']
        ];

        $this->expectException(InvalidMovementException::class);
        $this->expectExceptionMessage('Position (5, 5) is already occupied by another unit.');

        $this->policy->validateUnitCreation($position, $terrain, $existingUnits);
    }

    #[DataProvider('passableTerrainProvider')]
    public function testAcceptsAllPassableTerrainTypes(TerrainType $terrain): void
    {
        $position = new Position(5, 5);
        $existingUnits = [];

        $result = $this->policy->canCreateUnit($position, $terrain, $existingUnits);

        $this->assertTrue($result);
    }

    public static function passableTerrainProvider(): array
    {
        return [
            'plains' => [TerrainType::PLAINS],
            'forest' => [TerrainType::FOREST],
            'mountain' => [TerrainType::MOUNTAIN],
            'desert' => [TerrainType::DESERT],
            'swamp' => [TerrainType::SWAMP],
        ];
    }

    public function testRejectsWaterTerrain(): void
    {
        $position = new Position(5, 5);
        $existingUnits = [];

        $result = $this->policy->canCreateUnit($position, TerrainType::WATER, $existingUnits);

        $this->assertFalse($result);
    }
} 