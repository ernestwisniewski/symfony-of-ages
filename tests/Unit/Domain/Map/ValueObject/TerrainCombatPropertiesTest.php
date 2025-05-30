<?php

namespace Tests\Unit\Domain\Map\ValueObject;

use App\Domain\Map\ValueObject\TerrainCombatProperties;
use App\Domain\Map\Exception\InvalidTerrainDataException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for TerrainCombatProperties Value Object
 */
class TerrainCombatPropertiesTest extends TestCase
{
    public function testCreateTerrainCombatProperties(): void
    {
        $properties = new TerrainCombatProperties(3);
        
        $this->assertEquals(3, $properties->defenseBonus);
    }

    public function testCreateWithZeroDefenseBonus(): void
    {
        $properties = new TerrainCombatProperties(0);
        
        $this->assertEquals(0, $properties->defenseBonus);
    }

    public function testCreateWithNegativeDefenseBonusThrowsException(): void
    {
        $this->expectException(InvalidTerrainDataException::class);
        $this->expectExceptionMessage('Defense bonus cannot be negative');
        
        new TerrainCombatProperties(-1);
    }

    public function testProvidesDefensiveAdvantageWithHighDefense(): void
    {
        $properties = new TerrainCombatProperties(3);
        
        $this->assertTrue($properties->providesDefensiveAdvantage());
    }

    public function testProvidesDefensiveAdvantageWithVeryHighDefense(): void
    {
        $properties = new TerrainCombatProperties(4);
        
        $this->assertTrue($properties->providesDefensiveAdvantage());
    }

    public function testProvidesDefensiveAdvantageWithLowDefense(): void
    {
        $properties = new TerrainCombatProperties(2);
        
        $this->assertFalse($properties->providesDefensiveAdvantage());
    }

    public function testProvidesMinorDefenseWithLowDefense(): void
    {
        $properties = new TerrainCombatProperties(1);
        
        $this->assertTrue($properties->providesMinorDefense());
    }

    public function testProvidesMinorDefenseWithModerateDefense(): void
    {
        $properties = new TerrainCombatProperties(2);
        
        $this->assertTrue($properties->providesMinorDefense());
    }

    public function testProvidesMinorDefenseWithHighDefense(): void
    {
        $properties = new TerrainCombatProperties(3);
        
        $this->assertFalse($properties->providesMinorDefense());
    }

    public function testProvidesMinorDefenseWithZeroDefense(): void
    {
        $properties = new TerrainCombatProperties(0);
        
        $this->assertFalse($properties->providesMinorDefense());
    }

    public function testHasNoDefensiveValueWithZeroDefense(): void
    {
        $properties = new TerrainCombatProperties(0);
        
        $this->assertTrue($properties->hasNoDefensiveValue());
    }

    public function testHasNoDefensiveValueWithPositiveDefense(): void
    {
        $properties = new TerrainCombatProperties(1);
        
        $this->assertFalse($properties->hasNoDefensiveValue());
    }

    public function testIsFortifiedWithHighDefense(): void
    {
        $properties = new TerrainCombatProperties(4);
        
        $this->assertTrue($properties->isFortified());
    }

    public function testIsFortifiedWithVeryHighDefense(): void
    {
        $properties = new TerrainCombatProperties(5);
        
        $this->assertTrue($properties->isFortified());
    }

    public function testIsFortifiedWithLowerDefense(): void
    {
        $properties = new TerrainCombatProperties(3);
        
        $this->assertFalse($properties->isFortified());
    }

    public function testToArrayStructure(): void
    {
        $properties = new TerrainCombatProperties(3);
        $array = $properties->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('defenseBonus', $array);
        $this->assertArrayHasKey('defensiveLevel', $array);
        $this->assertArrayHasKey('providesDefensiveAdvantage', $array);
        
        $this->assertEquals(3, $array['defenseBonus']);
        $this->assertEquals('Strong', $array['defensiveLevel']);
        $this->assertTrue($array['providesDefensiveAdvantage']);
    }

    #[DataProvider('defensiveLevelProvider')]
    public function testDefensiveLevels(int $defenseBonus, string $expectedLevel): void
    {
        $properties = new TerrainCombatProperties($defenseBonus);
        $array = $properties->toArray();
        
        $this->assertEquals($expectedLevel, $array['defensiveLevel']);
    }

    #[DataProvider('tacticalAdvantageProvider')]
    public function testTacticalAdvantageLogic(
        int $defenseBonus, 
        bool $expectedDefensiveAdvantage, 
        bool $expectedMinorDefense, 
        bool $expectedNoDefense, 
        bool $expectedFortified
    ): void {
        $properties = new TerrainCombatProperties($defenseBonus);
        
        $this->assertEquals($expectedDefensiveAdvantage, $properties->providesDefensiveAdvantage());
        $this->assertEquals($expectedMinorDefense, $properties->providesMinorDefense());
        $this->assertEquals($expectedNoDefense, $properties->hasNoDefensiveValue());
        $this->assertEquals($expectedFortified, $properties->isFortified());
    }

    public function testDefensiveAdvantageAndFortificationLogic(): void
    {
        // Fortified terrain should always provide defensive advantage
        $fortified = new TerrainCombatProperties(4);
        $this->assertTrue($fortified->isFortified());
        $this->assertTrue($fortified->providesDefensiveAdvantage());
        
        // High defense should provide advantage but may not be fortified
        $highDefense = new TerrainCombatProperties(3);
        $this->assertTrue($highDefense->providesDefensiveAdvantage());
        $this->assertFalse($highDefense->isFortified());
    }

    public function testMutuallyExclusiveDefensiveStates(): void
    {
        // No defense and minor defense should be mutually exclusive
        $noDefense = new TerrainCombatProperties(0);
        $this->assertTrue($noDefense->hasNoDefensiveValue());
        $this->assertFalse($noDefense->providesMinorDefense());
        
        // Minor defense and major advantage should be mutually exclusive
        $minorDefense = new TerrainCombatProperties(1);
        $this->assertTrue($minorDefense->providesMinorDefense());
        $this->assertFalse($minorDefense->providesDefensiveAdvantage());
    }

    public function testValueObjectImmutability(): void
    {
        $properties1 = new TerrainCombatProperties(2);
        $properties2 = new TerrainCombatProperties(2);
        
        // Same values should create equivalent objects
        $this->assertEquals($properties1->defenseBonus, $properties2->defenseBonus);
        $this->assertEquals($properties1->providesDefensiveAdvantage(), $properties2->providesDefensiveAdvantage());
        $this->assertEquals($properties1->toArray(), $properties2->toArray());
    }

    public function testReadonlyProperty(): void
    {
        $properties = new TerrainCombatProperties(2);
        
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
        
        $this->assertFalse($hasSetters, 'TerrainCombatProperties should not have public setters');
    }

    public static function defensiveLevelProvider(): array
    {
        return [
            'None' => [0, 'None'],
            'Minor' => [1, 'Minor'],
            'Moderate' => [2, 'Moderate'],
            'Strong' => [3, 'Strong'],
            'Fortress Level 1' => [4, 'Fortress Level 1'],
            'Fortress Level 2' => [5, 'Fortress Level 2'],
            'Heavily Fortified' => [10, 'Heavily Fortified'],
        ];
    }

    public static function tacticalAdvantageProvider(): array
    {
        return [
            'No defense' => [0, false, false, true, false],
            'Minimal defense' => [1, false, true, false, false],
            'Light defense' => [2, false, true, false, false],
            'Moderate defense' => [3, true, false, false, false],
            'Fortified defense' => [4, true, false, false, true],
            'Heavy fortification' => [5, true, false, false, true],
            'Maximum defense' => [10, true, false, false, true],
        ];
    }
} 