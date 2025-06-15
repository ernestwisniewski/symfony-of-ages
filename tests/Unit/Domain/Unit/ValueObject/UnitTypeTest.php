<?php

namespace Tests\Unit\Domain\Unit\ValueObject;

use App\Domain\Unit\ValueObject\UnitType;
use PHPUnit\Framework\TestCase;
use ValueError;

class UnitTypeTest extends TestCase
{
    public function testAllUnitTypesHaveValidValues(): void
    {
        $this->assertEquals('warrior', UnitType::WARRIOR->value);
        $this->assertEquals('settler', UnitType::SETTLER->value);
        $this->assertEquals('archer', UnitType::ARCHER->value);
        $this->assertEquals('cavalry', UnitType::CAVALRY->value);
        $this->assertEquals('scout', UnitType::SCOUT->value);
        $this->assertEquals('siege_engine', UnitType::SIEGE_ENGINE->value);
    }

    public function testWarriorStats(): void
    {
        $warrior = UnitType::WARRIOR;

        $this->assertEquals(15, $warrior->getAttackPower());
        $this->assertEquals(12, $warrior->getDefensePower());
        $this->assertEquals(100, $warrior->getMaxHealth());
        $this->assertEquals(2, $warrior->getMovementRange());
    }

    public function testSettlerStats(): void
    {
        $settler = UnitType::SETTLER;

        $this->assertEquals(5, $settler->getAttackPower());
        $this->assertEquals(8, $settler->getDefensePower());
        $this->assertEquals(80, $settler->getMaxHealth());
        $this->assertEquals(2, $settler->getMovementRange());
    }

    public function testArcherStats(): void
    {
        $archer = UnitType::ARCHER;

        $this->assertEquals(12, $archer->getAttackPower());
        $this->assertEquals(8, $archer->getDefensePower());
        $this->assertEquals(80, $archer->getMaxHealth());
        $this->assertEquals(2, $archer->getMovementRange());
    }

    public function testCavalryStats(): void
    {
        $cavalry = UnitType::CAVALRY;

        $this->assertEquals(18, $cavalry->getAttackPower());
        $this->assertEquals(10, $cavalry->getDefensePower());
        $this->assertEquals(90, $cavalry->getMaxHealth());
        $this->assertEquals(4, $cavalry->getMovementRange());
    }

    public function testScoutStats(): void
    {
        $scout = UnitType::SCOUT;

        $this->assertEquals(8, $scout->getAttackPower());
        $this->assertEquals(6, $scout->getDefensePower());
        $this->assertEquals(60, $scout->getMaxHealth());
        $this->assertEquals(5, $scout->getMovementRange());
    }

    public function testSiegeEngineStats(): void
    {
        $siegeEngine = UnitType::SIEGE_ENGINE;

        $this->assertEquals(25, $siegeEngine->getAttackPower());
        $this->assertEquals(5, $siegeEngine->getDefensePower());
        $this->assertEquals(120, $siegeEngine->getMaxHealth());
        $this->assertEquals(1, $siegeEngine->getMovementRange());
    }

    public function testCanCreateFromString(): void
    {
        $this->assertEquals(UnitType::WARRIOR, UnitType::from('warrior'));
        $this->assertEquals(UnitType::SETTLER, UnitType::from('settler'));
        $this->assertEquals(UnitType::ARCHER, UnitType::from('archer'));
        $this->assertEquals(UnitType::CAVALRY, UnitType::from('cavalry'));
        $this->assertEquals(UnitType::SCOUT, UnitType::from('scout'));
        $this->assertEquals(UnitType::SIEGE_ENGINE, UnitType::from('siege_engine'));
    }

    public function testThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        UnitType::from('invalid_unit');
    }
}
