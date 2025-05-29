<?php

namespace App\Tests\Unit\Domain\Player\ValueObject;

use App\Domain\Player\ValueObject\Position;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Position value object
 */
class PositionTest extends TestCase
{
    public function testCanCreatePosition(): void
    {
        $position = new Position(5, 10);
        
        $this->assertEquals(5, $position->getRow());
        $this->assertEquals(10, $position->getCol());
    }

    public function testCanCreatePositionWithZeroCoordinates(): void
    {
        $position = new Position(0, 0);
        
        $this->assertEquals(0, $position->getRow());
        $this->assertEquals(0, $position->getCol());
    }

    public function testCanCreatePositionWithNegativeCoordinates(): void
    {
        $position = new Position(-5, -10);
        
        $this->assertEquals(-5, $position->getRow());
        $this->assertEquals(-10, $position->getCol());
    }

    public function testEqualsReturnsTrueForSamePosition(): void
    {
        $position1 = new Position(5, 10);
        $position2 = new Position(5, 10);
        
        $this->assertTrue($position1->equals($position2));
    }

    public function testEqualsReturnsFalseForDifferentPosition(): void
    {
        $position1 = new Position(5, 10);
        $position2 = new Position(5, 11);
        
        $this->assertFalse($position1->equals($position2));
    }

    public function testIsValidForMapWithValidPosition(): void
    {
        $position = new Position(5, 10);
        
        $this->assertTrue($position->isValidForMap(20, 20));
    }

    public function testIsValidForMapWithInvalidPosition(): void
    {
        $position = new Position(25, 10);
        
        $this->assertFalse($position->isValidForMap(20, 20));
    }

    public function testIsValidForMapWithNegativePosition(): void
    {
        $position = new Position(-1, 10);
        
        $this->assertFalse($position->isValidForMap(20, 20));
    }

    public function testDistanceToSamePosition(): void
    {
        $position1 = new Position(5, 5);
        $position2 = new Position(5, 5);
        
        $this->assertEquals(0, $position1->distanceTo($position2));
    }

    public function testDistanceToAdjacentPosition(): void
    {
        $position1 = new Position(5, 5);
        $position2 = new Position(5, 6);
        
        $this->assertEquals(1, $position1->distanceTo($position2));
    }

    public function testDistanceToDistantPosition(): void
    {
        $position1 = new Position(0, 0);
        $position2 = new Position(3, 3);
        
        $distance = $position1->distanceTo($position2);
        $this->assertGreaterThan(1, $distance);
    }

    public function testToArray(): void
    {
        $position = new Position(5, 10);
        $expected = ['row' => 5, 'col' => 10];
        
        $this->assertEquals($expected, $position->toArray());
    }

    public function testFromArray(): void
    {
        $data = ['row' => 5, 'col' => 10];
        $position = Position::fromArray($data);
        
        $this->assertEquals(5, $position->getRow());
        $this->assertEquals(10, $position->getCol());
    }

    public function testToString(): void
    {
        $position = new Position(5, 10);
        
        $this->assertEquals('(5, 10)', $position->__toString());
    }
} 