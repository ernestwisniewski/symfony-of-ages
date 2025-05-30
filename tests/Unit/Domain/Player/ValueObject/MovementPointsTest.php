<?php

namespace Tests\Unit\Domain\Player\ValueObject;

use App\Domain\Player\ValueObject\MovementPoints;
use App\Domain\Player\Exception\InvalidPlayerDataException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MovementPoints value object
 */
class MovementPointsTest extends TestCase
{
    public function testCanCreateMovementPointsWithValidValues(): void
    {
        $movementPoints = new MovementPoints(2, 3);
        
        $this->assertEquals(2, $movementPoints->current);
        $this->assertEquals(3, $movementPoints->maximum);
    }

    public function testCanCreateMovementPointsWithZeroValues(): void
    {
        $movementPoints = new MovementPoints(0, 0);
        
        $this->assertEquals(0, $movementPoints->current);
        $this->assertEquals(0, $movementPoints->maximum);
    }

    public function testThrowsExceptionForNegativeMaximum(): void
    {
        $this->expectException(InvalidPlayerDataException::class);
        $this->expectExceptionMessage('Maximum movement points cannot be negative');
        
        new MovementPoints(1, -1);
    }

    public function testThrowsExceptionForNegativeCurrent(): void
    {
        $this->expectException(InvalidPlayerDataException::class);
        $this->expectExceptionMessage('Current movement points cannot be negative');
        
        new MovementPoints(-1, 3);
    }

    public function testThrowsExceptionWhenCurrentExceedsMaximum(): void
    {
        $this->expectException(InvalidPlayerDataException::class);
        $this->expectExceptionMessage('Current movement points cannot exceed maximum');
        
        new MovementPoints(5, 3);
    }

    public function testCanSpendReturnsTrueForValidCost(): void
    {
        $movementPoints = new MovementPoints(3, 3);
        
        $this->assertTrue($movementPoints->canSpend(2));
    }

    public function testCanSpendReturnsFalseForInsufficientPoints(): void
    {
        $movementPoints = new MovementPoints(1, 3);
        
        $this->assertFalse($movementPoints->canSpend(2));
    }

    public function testCanSpendReturnsFalseForNegativeCost(): void
    {
        $movementPoints = new MovementPoints(3, 3);
        
        $this->assertFalse($movementPoints->canSpend(-1));
    }

    public function testCanSpendReturnsTrueForZeroCost(): void
    {
        $movementPoints = new MovementPoints(3, 3);
        
        $this->assertTrue($movementPoints->canSpend(0));
    }

    public function testSpendReturnsNewInstanceWithReducedPoints(): void
    {
        $movementPoints = new MovementPoints(3, 3);
        $newMovementPoints = $movementPoints->spend(2);
        
        $this->assertEquals(1, $newMovementPoints->current);
        $this->assertEquals(3, $newMovementPoints->maximum);
        // Original should be unchanged
        $this->assertEquals(3, $movementPoints->current);
    }

    public function testSpendThrowsExceptionForInsufficientPoints(): void
    {
        $movementPoints = new MovementPoints(1, 3);
        
        $this->expectException(InvalidPlayerDataException::class);
        $this->expectExceptionMessage('Cannot spend 2 movement points. Available: 1');
        
        $movementPoints->spend(2);
    }

    public function testRestoreReturnsNewInstanceWithFullPoints(): void
    {
        $movementPoints = new MovementPoints(1, 3);
        $restoredPoints = $movementPoints->restore();
        
        $this->assertEquals(3, $restoredPoints->current);
        $this->assertEquals(3, $restoredPoints->maximum);
        // Original should be unchanged
        $this->assertEquals(1, $movementPoints->current);
    }

    public function testHasPointsRemainingReturnsTrueForPositivePoints(): void
    {
        $movementPoints = new MovementPoints(1, 3);
        
        $this->assertTrue($movementPoints->hasPointsRemaining());
    }

    public function testHasPointsRemainingReturnsFalseForZeroPoints(): void
    {
        $movementPoints = new MovementPoints(0, 3);
        
        $this->assertFalse($movementPoints->hasPointsRemaining());
    }

    public function testIsEmptyReturnsTrueForZeroPoints(): void
    {
        $movementPoints = new MovementPoints(0, 3);
        
        $this->assertTrue($movementPoints->isEmpty());
    }

    public function testIsEmptyReturnsFalseForPositivePoints(): void
    {
        $movementPoints = new MovementPoints(1, 3);
        
        $this->assertFalse($movementPoints->isEmpty());
    }

    public function testToArray(): void
    {
        $movementPoints = new MovementPoints(2, 3);
        $expected = ['current' => 2, 'maximum' => 3];
        
        $this->assertEquals($expected, $movementPoints->toArray());
    }

    public function testFromArray(): void
    {
        $data = ['current' => 2, 'maximum' => 3];
        $movementPoints = MovementPoints::fromArray($data);
        
        $this->assertEquals(2, $movementPoints->current);
        $this->assertEquals(3, $movementPoints->maximum);
    }

    public function testCreateFull(): void
    {
        $movementPoints = MovementPoints::createFull(5);
        
        $this->assertEquals(5, $movementPoints->current);
        $this->assertEquals(5, $movementPoints->maximum);
    }
} 