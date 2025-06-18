<?php

namespace App\Tests\Unit\Domain\Visibility\Service;

use App\Domain\Shared\ValueObject\Position;
use App\Domain\Unit\ValueObject\UnitType;
use App\Domain\Visibility\Service\VisibilityCalculator;
use PHPUnit\Framework\TestCase;

class VisibilityCalculatorTest extends TestCase
{
    private VisibilityCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new VisibilityCalculator();
    }

    private function containsPosition(array $positions, Position $expected): bool
    {
        foreach ($positions as $pos) {
            if ($pos->x === $expected->x && $pos->y === $expected->y) {
                return true;
            }
        }
        return false;
    }

    private function containsXY(array $positions, int $x, int $y): bool
    {
        foreach ($positions as $pos) {
            if ($pos->x === $x && $pos->y === $y) {
                return true;
            }
        }
        return false;
    }

    public function testCalculateUnitVisibilityForScout(): void
    {
        $position = new Position(5, 5);
        $unitType = UnitType::SCOUT;
        $visibleHexes = $this->calculator->calculateUnitVisibility($position, $unitType);
        $this->assertCount(37, $visibleHexes);
        $this->assertTrue($this->containsPosition($visibleHexes, new Position(5, 5)));
        $this->assertTrue($this->containsPosition($visibleHexes, new Position(7, 5)));
        $this->assertTrue($this->containsPosition($visibleHexes, new Position(3, 5)));
    }

    public function testCalculateUnitVisibilityForWarrior(): void
    {
        $position = new Position(5, 5);
        $unitType = UnitType::WARRIOR;
        $visibleHexes = $this->calculator->calculateUnitVisibility($position, $unitType);
        $this->assertCount(7, $visibleHexes);
        $this->assertTrue($this->containsPosition($visibleHexes, new Position(5, 5)));
        $this->assertTrue($this->containsPosition($visibleHexes, new Position(6, 5)));
        $this->assertTrue($this->containsPosition($visibleHexes, new Position(4, 5)));
    }

    public function testCalculateCityVisibilityLevel1(): void
    {
        $position = new Position(5, 5);
        $cityLevel = 1;
        $visibleHexes = $this->calculator->calculateCityVisibility($position, $cityLevel);
        $this->assertCount(19, $visibleHexes);
        $this->assertTrue($this->containsPosition($visibleHexes, new Position(5, 5)));
        $this->assertTrue($this->containsPosition($visibleHexes, new Position(7, 5)));
    }

    public function testCalculateCityVisibilityLevel3(): void
    {
        $position = new Position(5, 5);
        $cityLevel = 3;
        $visibleHexes = $this->calculator->calculateCityVisibility($position, $cityLevel);
        $this->assertCount(61, $visibleHexes);
        $this->assertTrue($this->containsPosition($visibleHexes, new Position(5, 5)));
        $this->assertTrue($this->containsPosition($visibleHexes, new Position(9, 5)));
    }

    public function testCalculateHexesInRadiusAtEdge(): void
    {
        $position = new Position(0, 0);
        $unitType = UnitType::SCOUT;
        $visibleHexes = $this->calculator->calculateUnitVisibility($position, $unitType);
        $expectedCount = 10;
        $this->assertCount($expectedCount, $visibleHexes);
        $this->assertTrue($this->containsPosition($visibleHexes, new Position(0, 0)));
        $this->assertTrue($this->containsPosition($visibleHexes, new Position(3, 0)));
        $this->assertFalse($this->containsXY($visibleHexes, -1, -1));
    }

    public function testIsInVisibilityRange(): void
    {
        $from = new Position(5, 5);
        $to = new Position(6, 5);
        $this->assertTrue($this->calculator->isInVisibilityRange($from, $to, 1));
        $this->assertTrue($this->calculator->isInVisibilityRange($from, $to, 2));
        $this->assertFalse($this->calculator->isInVisibilityRange($from, $to, 0));
    }

    public function testIsInVisibilityRangeFarDistance(): void
    {
        $from = new Position(5, 5);
        $to = new Position(8, 8);
        $this->assertFalse($this->calculator->isInVisibilityRange($from, $to, 1));
        $this->assertFalse($this->calculator->isInVisibilityRange($from, $to, 2));
        $this->assertTrue($this->calculator->isInVisibilityRange($from, $to, 6));
    }

    public function testUnitVisibilityRadiuses(): void
    {
        $position = new Position(5, 5);
        $scoutHexes = $this->calculator->calculateUnitVisibility($position, UnitType::SCOUT);
        $cavalryHexes = $this->calculator->calculateUnitVisibility($position, UnitType::CAVALRY);
        $warriorHexes = $this->calculator->calculateUnitVisibility($position, UnitType::WARRIOR);
        $this->assertGreaterThan(count($warriorHexes), count($cavalryHexes));
        $this->assertGreaterThan(count($cavalryHexes), count($scoutHexes));
    }
} 