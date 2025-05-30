<?php

namespace Tests\Unit\Domain\Map\ValueObject;

use App\Domain\Map\ValueObject\TerrainProperties;
use App\Domain\Map\ValueObject\TerrainVisualProperties;
use App\Domain\Map\ValueObject\TerrainMovementProperties;
use App\Domain\Map\ValueObject\TerrainCombatProperties;
use App\Domain\Map\ValueObject\TerrainEconomicProperties;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for TerrainProperties aggregate Value Object
 */
class TerrainPropertiesTest extends TestCase
{
    private TerrainProperties $properties;

    protected function setUp(): void
    {
        $this->properties = new TerrainProperties(
            new TerrainVisualProperties('Forest', 0x228B22),
            new TerrainMovementProperties(2),
            new TerrainCombatProperties(3),
            new TerrainEconomicProperties(3)
        );
    }

    public function testCreateTerrainProperties(): void
    {
        $this->assertInstanceOf(TerrainProperties::class, $this->properties);
    }

    public function testVisualPropertiesAccessor(): void
    {
        $visual = $this->properties->visual();
        
        $this->assertInstanceOf(TerrainVisualProperties::class, $visual);
        $this->assertEquals('Forest', $visual->getName());
        $this->assertEquals(0x228B22, $visual->getColor());
    }

    public function testMovementPropertiesAccessor(): void
    {
        $movement = $this->properties->movement();
        
        $this->assertInstanceOf(TerrainMovementProperties::class, $movement);
        $this->assertEquals(2, $movement->getMovementCost());
        $this->assertTrue($movement->isPassable());
    }

    public function testCombatPropertiesAccessor(): void
    {
        $combat = $this->properties->combat();
        
        $this->assertInstanceOf(TerrainCombatProperties::class, $combat);
        $this->assertEquals(3, $combat->getDefenseBonus());
        $this->assertTrue($combat->providesDefensiveAdvantage());
    }

    public function testEconomicPropertiesAccessor(): void
    {
        $economic = $this->properties->economic();
        
        $this->assertInstanceOf(TerrainEconomicProperties::class, $economic);
        $this->assertEquals(3, $economic->getResourceYield());
        $this->assertTrue($economic->isResourceRich());
    }

    public function testQuickAccessMethods(): void
    {
        $this->assertEquals('Forest', $this->properties->getName());
        $this->assertEquals(0x228B22, $this->properties->getColor());
        $this->assertEquals(2, $this->properties->getMovementCost());
        $this->assertTrue($this->properties->isPassable());
        $this->assertEquals(3, $this->properties->getDefenseBonus());
        $this->assertEquals(3, $this->properties->getResourceYield());
    }

    public function testTacticalAnalysisMethods(): void
    {
        $this->assertTrue($this->properties->isTacticallyAdvantaged());
        $this->assertTrue($this->properties->isEconomicallyViable());
        $this->assertTrue($this->properties->isStrategicallyImportant());
    }

    public function testStrategicallyImportantWhenOnlyTactical(): void
    {
        $tacticalOnly = new TerrainProperties(
            new TerrainVisualProperties('Mountain', 0x808080),
            new TerrainMovementProperties(3),
            new TerrainCombatProperties(4), // High defense
            new TerrainEconomicProperties(1) // Low resources
        );
        
        $this->assertTrue($tacticalOnly->isTacticallyAdvantaged());
        $this->assertFalse($tacticalOnly->isEconomicallyViable());
        $this->assertTrue($tacticalOnly->isStrategicallyImportant());
    }

    public function testStrategicallyImportantWhenOnlyEconomic(): void
    {
        $economicOnly = new TerrainProperties(
            new TerrainVisualProperties('Plains', 0x90EE90),
            new TerrainMovementProperties(1),
            new TerrainCombatProperties(1), // Low defense
            new TerrainEconomicProperties(4) // High resources
        );
        
        $this->assertFalse($economicOnly->isTacticallyAdvantaged());
        $this->assertTrue($economicOnly->isEconomicallyViable());
        $this->assertTrue($economicOnly->isStrategicallyImportant());
    }

    public function testNotStrategicallyImportantWhenBothLow(): void
    {
        $lowValue = new TerrainProperties(
            new TerrainVisualProperties('Desert', 0xF4A460),
            new TerrainMovementProperties(2),
            new TerrainCombatProperties(1), // Low defense
            new TerrainEconomicProperties(1) // Low resources
        );
        
        $this->assertFalse($lowValue->isTacticallyAdvantaged());
        $this->assertFalse($lowValue->isEconomicallyViable());
        $this->assertFalse($lowValue->isStrategicallyImportant());
    }

    public function testToLegacyArray(): void
    {
        $legacyArray = $this->properties->toLegacyArray();
        
        $this->assertIsArray($legacyArray);
        $this->assertArrayHasKey('name', $legacyArray);
        $this->assertArrayHasKey('color', $legacyArray);
        $this->assertArrayHasKey('movementCost', $legacyArray);
        $this->assertArrayHasKey('defense', $legacyArray);
        $this->assertArrayHasKey('resources', $legacyArray);
        
        $this->assertEquals('Forest', $legacyArray['name']);
        $this->assertEquals(0x228B22, $legacyArray['color']);
        $this->assertEquals(2, $legacyArray['movementCost']);
        $this->assertEquals(3, $legacyArray['defense']);
        $this->assertEquals(3, $legacyArray['resources']);
    }

    public function testToDetailedArray(): void
    {
        $detailedArray = $this->properties->toDetailedArray();
        
        $this->assertIsArray($detailedArray);
        $this->assertArrayHasKey('visual', $detailedArray);
        $this->assertArrayHasKey('movement', $detailedArray);
        $this->assertArrayHasKey('combat', $detailedArray);
        $this->assertArrayHasKey('economic', $detailedArray);
        $this->assertArrayHasKey('legacy', $detailedArray);
        
        // Verify that each section contains expected data
        $this->assertIsArray($detailedArray['visual']);
        $this->assertIsArray($detailedArray['movement']);
        $this->assertIsArray($detailedArray['combat']);
        $this->assertIsArray($detailedArray['economic']);
        $this->assertIsArray($detailedArray['legacy']);
        
        // Verify legacy compatibility
        $this->assertEquals($this->properties->toLegacyArray(), $detailedArray['legacy']);
    }

    public function testValueObjectImmutability(): void
    {
        $properties1 = new TerrainProperties(
            new TerrainVisualProperties('Forest', 0x228B22),
            new TerrainMovementProperties(2),
            new TerrainCombatProperties(3),
            new TerrainEconomicProperties(3)
        );
        
        $properties2 = new TerrainProperties(
            new TerrainVisualProperties('Forest', 0x228B22),
            new TerrainMovementProperties(2),
            new TerrainCombatProperties(3),
            new TerrainEconomicProperties(3)
        );
        
        // Same values should create equivalent objects
        $this->assertEquals($properties1->getName(), $properties2->getName());
        $this->assertEquals($properties1->toLegacyArray(), $properties2->toLegacyArray());
        $this->assertEquals($properties1->toDetailedArray(), $properties2->toDetailedArray());
    }

    public function testReadonlyProperty(): void
    {
        // Test that properties are readonly by ensuring they don't have setters
        $reflection = new \ReflectionClass($this->properties);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        $hasSetters = false;
        foreach ($methods as $method) {
            if (str_starts_with($method->getName(), 'set')) {
                $hasSetters = true;
                break;
            }
        }
        
        $this->assertFalse($hasSetters, 'TerrainProperties should not have public setters');
    }

    public function testPassabilityConsistency(): void
    {
        // Test with impassable terrain
        $impassable = new TerrainProperties(
            new TerrainVisualProperties('Water', 0x4169E1),
            new TerrainMovementProperties(0), // Impassable
            new TerrainCombatProperties(0),
            new TerrainEconomicProperties(1)
        );
        
        $this->assertFalse($impassable->isPassable());
        $this->assertEquals(0, $impassable->getMovementCost());
        $this->assertTrue($impassable->movement()->isImpassable());
        
        // Test with passable terrain
        $this->assertTrue($this->properties->isPassable());
        $this->assertGreaterThan(0, $this->properties->getMovementCost());
        $this->assertFalse($this->properties->movement()->isImpassable());
    }

    #[DataProvider('terrainTypesProvider')]
    public function testDifferentTerrainTypes(
        string $name,
        int $color,
        int $movementCost,
        int $defenseBonus,
        int $resourceYield,
        bool $expectedPassable,
        bool $expectedTactical,
        bool $expectedEconomic,
        bool $expectedStrategic
    ): void {
        $properties = new TerrainProperties(
            new TerrainVisualProperties($name, $color),
            new TerrainMovementProperties($movementCost),
            new TerrainCombatProperties($defenseBonus),
            new TerrainEconomicProperties($resourceYield)
        );
        
        $this->assertEquals($expectedPassable, $properties->isPassable());
        $this->assertEquals($expectedTactical, $properties->isTacticallyAdvantaged());
        $this->assertEquals($expectedEconomic, $properties->isEconomicallyViable());
        $this->assertEquals($expectedStrategic, $properties->isStrategicallyImportant());
    }

    public function testBackwardCompatibilityWithQuickAccessors(): void
    {
        $legacyArray = $this->properties->toLegacyArray();
        
        // Quick accessors should match legacy array values
        $this->assertEquals($legacyArray['name'], $this->properties->getName());
        $this->assertEquals($legacyArray['color'], $this->properties->getColor());
        $this->assertEquals($legacyArray['movementCost'], $this->properties->getMovementCost());
        $this->assertEquals($legacyArray['defense'], $this->properties->getDefenseBonus());
        $this->assertEquals($legacyArray['resources'], $this->properties->getResourceYield());
    }

    public static function terrainTypesProvider(): array
    {
        return [
            // [name, color, movementCost, defenseBonus, resourceYield, expectedPassable, expectedTactical, expectedEconomic, expectedStrategic]
            'Plains' => ['Plains', 0x90EE90, 1, 1, 2, true, false, false, false],
            'Forest' => ['Forest', 0x228B22, 2, 3, 3, true, true, true, true],
            'Mountain' => ['Mountain', 0x808080, 3, 4, 4, true, true, true, true],
            'Water' => ['Water', 0x4169E1, 0, 0, 1, false, false, false, false],
            'Desert' => ['Desert', 0xF4A460, 2, 1, 1, true, false, false, false],
            'Swamp' => ['Swamp', 0x556B2F, 3, 2, 2, true, false, false, false],
        ];
    }
} 