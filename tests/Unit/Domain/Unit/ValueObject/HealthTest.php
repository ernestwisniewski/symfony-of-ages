<?php

namespace Tests\Unit\Domain\Unit\ValueObject;

use App\Domain\Unit\ValueObject\Health;
use App\Domain\Unit\ValueObject\InvalidHealthException;
use PHPUnit\Framework\TestCase;

class HealthTest extends TestCase
{
    public function testCanCreateValidHealth(): void
    {
        $health = new Health(50, 100);

        $this->assertEquals(50, $health->current);
        $this->assertEquals(100, $health->maximum);
    }

    public function testCanCreateFullHealth(): void
    {
        $health = Health::full(100);

        $this->assertEquals(100, $health->current);
        $this->assertEquals(100, $health->maximum);
        $this->assertTrue($health->isFullHealth());
    }

    public function testThrowsExceptionForNegativeCurrent(): void
    {
        $this->expectException(InvalidHealthException::class);
        $this->expectExceptionMessage('Health cannot be negative: -10');

        new Health(-10, 100);
    }

    public function testThrowsExceptionForZeroMaximum(): void
    {
        $this->expectException(InvalidHealthException::class);
        $this->expectExceptionMessage('Maximum health must be positive: 0');

        new Health(50, 0);
    }

    public function testThrowsExceptionWhenCurrentExceedsMaximum(): void
    {
        $this->expectException(InvalidHealthException::class);
        $this->expectExceptionMessage('Current health (150) cannot exceed maximum (100)');

        new Health(150, 100);
    }

    public function testIsDeadReturnsTrueWhenCurrentIsZero(): void
    {
        $health = new Health(0, 100);

        $this->assertTrue($health->isDead());
    }

    public function testIsDeadReturnsFalseWhenCurrentIsGreaterThanZero(): void
    {
        $health = new Health(1, 100);

        $this->assertFalse($health->isDead());
    }

    public function testIsFullHealthReturnsTrueWhenCurrentEqualsMaximum(): void
    {
        $health = new Health(100, 100);

        $this->assertTrue($health->isFullHealth());
    }

    public function testIsFullHealthReturnsFalseWhenCurrentLessThanMaximum(): void
    {
        $health = new Health(99, 100);

        $this->assertFalse($health->isFullHealth());
    }

    public function testGetHealthPercentageReturnsCorrectValue(): void
    {
        $health = new Health(75, 100);

        $this->assertEquals(75.0, $health->getHealthPercentage());
    }

    public function testTakeDamageReducesCurrentHealth(): void
    {
        $health = new Health(100, 100);
        $damagedHealth = $health->takeDamage(30);

        $this->assertEquals(70, $damagedHealth->current);
        $this->assertEquals(100, $damagedHealth->maximum);
    }

    public function testTakeDamageCannotReduceBelowZero(): void
    {
        $health = new Health(20, 100);
        $damagedHealth = $health->takeDamage(50);

        $this->assertEquals(0, $damagedHealth->current);
        $this->assertTrue($damagedHealth->isDead());
    }

    public function testTakeDamageThrowsExceptionForNegativeDamage(): void
    {
        $health = new Health(100, 100);

        $this->expectException(InvalidHealthException::class);
        $this->expectExceptionMessage('Damage cannot be negative: -10');

        $health->takeDamage(-10);
    }

    public function testHealIncreasesCurrentHealth(): void
    {
        $health = new Health(50, 100);
        $healedHealth = $health->heal(30);

        $this->assertEquals(80, $healedHealth->current);
        $this->assertEquals(100, $healedHealth->maximum);
    }

    public function testHealCannotExceedMaximum(): void
    {
        $health = new Health(90, 100);
        $healedHealth = $health->heal(50);

        $this->assertEquals(100, $healedHealth->current);
        $this->assertTrue($healedHealth->isFullHealth());
    }

    public function testHealThrowsExceptionForNegativeHealing(): void
    {
        $health = new Health(50, 100);

        $this->expectException(InvalidHealthException::class);
        $this->expectExceptionMessage('Healing cannot be negative: -10');

        $health->heal(-10);
    }
}
