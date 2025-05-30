<?php

namespace Tests\Unit\Domain\Map\ValueObject;

use App\Domain\Map\ValueObject\TerrainMovementProperties;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for TerrainMovementProperties Value Object
 */
class TerrainMovementPropertiesTest extends TestCase
{
    public function testCreateTerrainMovementProperties(): void
    {
        $properties = new TerrainMovementProperties(2);
        
        $this->assertEquals(2, $properties->getMovementCost());
    }

    public function testCreateWithZeroMovementCost(): void
    {
        $properties = new TerrainMovementProperties(0);
        
        $this->assertEquals(0, $properties->getMovementCost());
    }

    public function testCreateWithNegativeMovementCostThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Movement cost cannot be negative');
        
        new TerrainMovementProperties(-1);
    }

    public function testIsPassableWithPositiveMovementCost(): void
    {
        $properties = new TerrainMovementProperties(1);
        
        $this->assertTrue($properties->isPassable());
    }

    public function testIsPassableWithZeroMovementCost(): void
    {
        $properties = new TerrainMovementProperties(0);
        
        $this->assertFalse($properties->isPassable());
    }

    public function testIsImpassableWithZeroMovementCost(): void
    {
        $properties = new TerrainMovementProperties(0);
        
        $this->assertTrue($properties->isImpassable());
    }

    public function testIsImpassableWithPositiveMovementCost(): void
    {
        $properties = new TerrainMovementProperties(1);
        
        $this->assertFalse($properties->isImpassable());
    }

    public function testIsEasyToTraverseWithMovementCostOne(): void
    {
        $properties = new TerrainMovementProperties(1);
        
        $this->assertTrue($properties->isEasyToTraverse());
    }

    public function testIsEasyToTraverseWithHigherMovementCost(): void
    {
        $properties = new TerrainMovementProperties(2);
        
        $this->assertFalse($properties->isEasyToTraverse());
    }

    public function testIsDifficultToTraverseWithHighMovementCost(): void
    {
        $properties = new TerrainMovementProperties(3);
        
        $this->assertTrue($properties->isDifficultToTraverse());
    }

    public function testIsDifficultToTraverseWithVeryHighMovementCost(): void
    {
        $properties = new TerrainMovementProperties(5);
        
        $this->assertTrue($properties->isDifficultToTraverse());
    }

    public function testIsDifficultToTraverseWithLowMovementCost(): void
    {
        $properties = new TerrainMovementProperties(2);
        
        $this->assertFalse($properties->isDifficultToTraverse());
    }

    public function testToArrayStructure(): void
    {
        $properties = new TerrainMovementProperties(2);
        $array = $properties->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('movementCost', $array);
        $this->assertArrayHasKey('passable', $array);
        $this->assertArrayHasKey('difficulty', $array);
        
        $this->assertEquals(2, $array['movementCost']);
        $this->assertTrue($array['passable']);
        $this->assertEquals('moderate', $array['difficulty']);
    }

    #[DataProvider('movementDifficultyProvider')]
    public function testMovementDifficultyLevels(int $movementCost, string $expectedDifficulty): void
    {
        $properties = new TerrainMovementProperties($movementCost);
        $array = $properties->toArray();
        
        $this->assertEquals($expectedDifficulty, $array['difficulty']);
    }

    #[DataProvider('passabilityProvider')]
    public function testPassabilityLogic(int $movementCost, bool $expectedPassable, bool $expectedImpassable): void
    {
        $properties = new TerrainMovementProperties($movementCost);
        
        $this->assertEquals($expectedPassable, $properties->isPassable());
        $this->assertEquals($expectedImpassable, $properties->isImpassable());
        $this->assertEquals($expectedPassable, !$expectedImpassable); // Should be opposite
    }

    #[DataProvider('traversalDifficultyProvider')]
    public function testTraversalDifficultyAssessment(
        int $movementCost, 
        bool $expectedEasy, 
        bool $expectedDifficult
    ): void {
        $properties = new TerrainMovementProperties($movementCost);
        
        $this->assertEquals($expectedEasy, $properties->isEasyToTraverse());
        $this->assertEquals($expectedDifficult, $properties->isDifficultToTraverse());
    }

    public function testValueObjectImmutability(): void
    {
        $properties1 = new TerrainMovementProperties(2);
        $properties2 = new TerrainMovementProperties(2);
        
        // Same values should create equivalent objects
        $this->assertEquals($properties1->getMovementCost(), $properties2->getMovementCost());
        $this->assertEquals($properties1->isPassable(), $properties2->isPassable());
        $this->assertEquals($properties1->toArray(), $properties2->toArray());
    }

    public function testReadonlyProperty(): void
    {
        $properties = new TerrainMovementProperties(1);
        
        // Test that properties are readonly by ensuring they don't have setters
        $reflection = new \ReflectionClass($properties);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        $hasSetters = false;
        foreach ($methods as $method) {
            if (str_starts_with($method->getName(), 'set')) {
                $hasSetters = true;
                break;
            }
        }
        
        $this->assertFalse($hasSetters, 'TerrainMovementProperties should not have public setters');
    }

    public static function movementDifficultyProvider(): array
    {
        return [
            'Impassable' => [0, 'impassable'],
            'Easy' => [1, 'easy'],
            'Moderate' => [2, 'moderate'],
            'Difficult' => [3, 'difficult'],
            'Very Difficult' => [4, 'difficult'],
            'Extremely Difficult' => [10, 'difficult'],
        ];
    }

    public static function passabilityProvider(): array
    {
        return [
            'Impassable water' => [0, false, true],
            'Easy plains' => [1, true, false],
            'Moderate forest' => [2, true, false],
            'Difficult mountain' => [3, true, false],
            'Very difficult terrain' => [5, true, false],
        ];
    }

    public static function traversalDifficultyProvider(): array
    {
        return [
            'Impassable' => [0, false, false],
            'Easy' => [1, true, false],
            'Moderate' => [2, false, false],
            'Difficult' => [3, false, true],
            'Very Difficult' => [4, false, true],
            'Extremely Difficult' => [10, false, true],
        ];
    }
} 