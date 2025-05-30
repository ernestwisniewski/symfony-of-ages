<?php

namespace Tests\Unit\Domain\Player\ValueObject;

use App\Domain\Player\ValueObject\Position;
use App\Domain\Player\Exception\InvalidPlayerDataException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for Position Value Object
 */
class PositionTest extends TestCase
{
    public function testCreatePositionWithValidCoordinates(): void
    {
        $position = new Position(5, 10);
        
        $this->assertEquals(5, $position->row);
        $this->assertEquals(10, $position->col);
    }

    public function testCreatePositionWithZeroCoordinates(): void
    {
        $position = new Position(0, 0);
        
        $this->assertEquals(0, $position->row);
        $this->assertEquals(0, $position->col);
    }

    public function testThrowsExceptionForNegativeRow(): void
    {
        $this->expectException(InvalidPlayerDataException::class);
        $this->expectExceptionMessage('Row cannot be negative');
        
        new Position(-1, 5);
    }

    public function testThrowsExceptionForNegativeCol(): void
    {
        $this->expectException(InvalidPlayerDataException::class);
        $this->expectExceptionMessage('Column cannot be negative');
        
        new Position(5, -1);
    }

    public function testEqualsReturnsTrueForSameCoordinates(): void
    {
        $position1 = new Position(5, 10);
        $position2 = new Position(5, 10);
        
        $this->assertTrue($position1->equals($position2));
    }

    public function testEqualsReturnsFalseForDifferentRow(): void
    {
        $position1 = new Position(5, 10);
        $position2 = new Position(6, 10);
        
        $this->assertFalse($position1->equals($position2));
    }

    public function testEqualsReturnsFalseForDifferentCol(): void
    {
        $position1 = new Position(5, 10);
        $position2 = new Position(5, 11);
        
        $this->assertFalse($position1->equals($position2));
    }

    public function testIsValidForMapReturnsTrueForValidPosition(): void
    {
        $position = new Position(5, 10);
        
        $this->assertTrue($position->isValidForMap(20, 20));
    }

    public function testIsValidForMapReturnsFalseForRowOutOfBounds(): void
    {
        $position = new Position(25, 10);
        
        $this->assertFalse($position->isValidForMap(20, 20));
    }

    public function testIsValidForMapReturnsFalseForColOutOfBounds(): void
    {
        $position = new Position(5, 25);
        
        $this->assertFalse($position->isValidForMap(20, 20));
    }

    public function testIsValidForMapWorksWithBoundaryPositions(): void
    {
        // Test corners and edges
        $this->assertTrue((new Position(0, 0))->isValidForMap(10, 10));
        $this->assertTrue((new Position(9, 9))->isValidForMap(10, 10));
        $this->assertTrue((new Position(0, 9))->isValidForMap(10, 10));
        $this->assertTrue((new Position(9, 0))->isValidForMap(10, 10));
        
        // Test out of bounds
        $this->assertFalse((new Position(10, 5))->isValidForMap(10, 10));
        $this->assertFalse((new Position(5, 10))->isValidForMap(10, 10));
    }

    #[DataProvider('distanceProvider')]
    public function testDistanceToCalculation(Position $from, Position $to, int $expectedDistance): void
    {
        $actualDistance = $from->distanceTo($to);
        
        $this->assertEquals($expectedDistance, $actualDistance);
    }

    public function testDistanceToSamePosition(): void
    {
        $position = new Position(5, 5);
        
        $this->assertEquals(0, $position->distanceTo($position));
    }

    public function testToArray(): void
    {
        $position = new Position(7, 12);
        $array = $position->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('row', $array);
        $this->assertArrayHasKey('col', $array);
        $this->assertEquals(7, $array['row']);
        $this->assertEquals(12, $array['col']);
    }

    public function testFromArray(): void
    {
        $data = ['row' => 15, 'col' => 8];
        $position = Position::fromArray($data);
        
        $this->assertEquals(15, $position->row);
        $this->assertEquals(8, $position->col);
    }

    public function testFromArrayWithMissingRow(): void
    {
        $this->expectException(InvalidPlayerDataException::class);
        $this->expectExceptionMessage('Row is required');
        
        Position::fromArray(['col' => 8]);
    }

    public function testFromArrayWithMissingCol(): void
    {
        $this->expectException(InvalidPlayerDataException::class);
        $this->expectExceptionMessage('Column is required');
        
        Position::fromArray(['row' => 15]);
    }

    public function testToString(): void
    {
        $position = new Position(3, 7);
        
        $this->assertEquals('(3, 7)', $position->__toString());
        $this->assertEquals('(3, 7)', (string)$position);
    }

    public function testValueObjectImmutability(): void
    {
        $position1 = new Position(5, 10);
        $position2 = new Position(5, 10);
        
        // Same values should create equivalent objects
        $this->assertEquals($position1->row, $position2->row);
        $this->assertEquals($position1->col, $position2->col);
        $this->assertEquals($position1->toArray(), $position2->toArray());
        $this->assertTrue($position1->equals($position2));
    }

    public function testReadonlyProperty(): void
    {
        $position = new Position(1, 1);
        
        // Test that position is readonly by ensuring it doesn't have setters
        $reflection = new \ReflectionClass($position);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        $hasSetters = false;
        foreach ($methods as $method) {
            if (str_starts_with($method->getName(), 'set')) {
                $hasSetters = true;
                break;
            }
        }
        
        $this->assertFalse($hasSetters, 'Position should not have public setters');
    }

    #[DataProvider('hexDistanceProvider')]
    public function testHexagonalDistanceCalculation(int $row1, int $col1, int $row2, int $col2, int $expectedDistance): void
    {
        $from = new Position($row1, $col1);
        $to = new Position($row2, $col2);
        
        $this->assertEquals($expectedDistance, $from->distanceTo($to));
    }

    public static function distanceProvider(): array
    {
        return [
            'Same position' => [new Position(0, 0), new Position(0, 0), 0],
            'Adjacent horizontal' => [new Position(0, 0), new Position(0, 1), 1],
            'Adjacent vertical' => [new Position(0, 0), new Position(1, 0), 1],
            'Two steps away' => [new Position(0, 0), new Position(0, 2), 2],
            'Diagonal distance' => [new Position(0, 0), new Position(1, 1), 1],
        ];
    }

    public static function hexDistanceProvider(): array
    {
        return [
            // Basic adjacent moves in hexagonal grid
            'Right neighbor' => [5, 5, 5, 6, 1],
            'Left neighbor' => [5, 5, 5, 4, 1],
            'Top-right (even row)' => [4, 5, 3, 5, 1],
            'Bottom-right (even row)' => [4, 5, 5, 5, 1],
            'Top-left (even row)' => [4, 5, 3, 4, 2],
            'Bottom-left (even row)' => [4, 5, 5, 4, 2],
            
            // Multi-step distances
            'Two steps horizontal' => [5, 5, 5, 7, 2],
            'Three steps' => [0, 0, 0, 3, 3],
            'Complex path' => [2, 2, 5, 4, 3],
        ];
    }
} 