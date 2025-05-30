<?php

namespace Tests\Unit\Domain\Map\ValueObject;

use App\Domain\Map\ValueObject\TerrainProperties;
use App\Domain\Map\ValueObject\TerrainVisualProperties;
use App\Domain\Map\ValueObject\TerrainMovementProperties;
use App\Domain\Map\ValueObject\TerrainCombatProperties;
use App\Domain\Map\ValueObject\TerrainEconomicProperties;
use App\Domain\Map\Enum\TerrainType;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for TerrainProperties Value Object
 */
class TerrainPropertiesTest extends TestCase
{
    private TerrainProperties $properties;

    protected function setUp(): void
    {
        // Standard forest terrain for testing
        $this->properties = new TerrainProperties(
            new TerrainVisualProperties('Forest', 0x228B22),
            new TerrainMovementProperties(2),
            new TerrainCombatProperties(3),
            new TerrainEconomicProperties(3)
        );
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(TerrainProperties::class, $this->properties);
        $this->assertInstanceOf(TerrainVisualProperties::class, $this->properties->visual);
        $this->assertInstanceOf(TerrainMovementProperties::class, $this->properties->movement);
        $this->assertInstanceOf(TerrainCombatProperties::class, $this->properties->combat);
        $this->assertInstanceOf(TerrainEconomicProperties::class, $this->properties->economic);
    }

    public function testPropertyHooks(): void
    {
        $this->assertEquals('Forest', $this->properties->name);
        $this->assertEquals(0x228B22, $this->properties->color);
        $this->assertEquals(2, $this->properties->movementCost);
        $this->assertTrue($this->properties->isPassable);
        $this->assertEquals(3, $this->properties->defenseBonus);
        $this->assertEquals(3, $this->properties->resourceYield);
        $this->assertEquals('#228B22', $this->properties->hexColor);
    }

    public function testComponentAccessMethods(): void
    {
        $visual = $this->properties->visual();
        $movement = $this->properties->movement();
        $combat = $this->properties->combat();
        $economic = $this->properties->economic();

        $this->assertInstanceOf(TerrainVisualProperties::class, $visual);
        $this->assertInstanceOf(TerrainMovementProperties::class, $movement);
        $this->assertInstanceOf(TerrainCombatProperties::class, $combat);
        $this->assertInstanceOf(TerrainEconomicProperties::class, $economic);

        // Verify they're the same instances as the public properties
        $this->assertSame($this->properties->visual, $visual);
        $this->assertSame($this->properties->movement, $movement);
        $this->assertSame($this->properties->combat, $combat);
        $this->assertSame($this->properties->economic, $economic);
    }

    public function testToArray(): void
    {
        $arrayData = $this->properties->toArray();

        $this->assertIsArray($arrayData);
        $this->assertArrayHasKey('name', $arrayData);
        $this->assertArrayHasKey('color', $arrayData);
        $this->assertArrayHasKey('movementCost', $arrayData);
        $this->assertArrayHasKey('defense', $arrayData);
        $this->assertArrayHasKey('resources', $arrayData);

        $this->assertEquals('Forest', $arrayData['name']);
        $this->assertEquals(0x228B22, $arrayData['color']);
        $this->assertEquals(2, $arrayData['movementCost']);
        $this->assertEquals(3, $arrayData['defense']);
        $this->assertEquals(3, $arrayData['resources']);
    }

    public function testToDetailedArray(): void
    {
        $detailedArray = $this->properties->toDetailedArray();

        $this->assertIsArray($detailedArray);
        $this->assertArrayHasKey('visual', $detailedArray);
        $this->assertArrayHasKey('movement', $detailedArray);
        $this->assertArrayHasKey('combat', $detailedArray);
        $this->assertArrayHasKey('economic', $detailedArray);
        $this->assertArrayHasKey('quick_access', $detailedArray);

        // Verify each component is properly structured
        $this->assertIsArray($detailedArray['visual']);
        $this->assertIsArray($detailedArray['movement']);
        $this->assertIsArray($detailedArray['combat']);
        $this->assertIsArray($detailedArray['economic']);

        // Verify consistency with quick access
        $this->assertEquals($this->properties->toArray(), $detailedArray['quick_access']);
    }

    public function testPropertyEquality(): void
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

        // Should have same array representation
        $this->assertEquals($properties1->toArray(), $properties2->toArray());
    }

    public function testPropertyAccessConsistency(): void
    {
        $arrayData = $this->properties->toArray();

        // Quick accessors should match array values
        $this->assertEquals($arrayData['name'], $this->properties->name);
        $this->assertEquals($arrayData['color'], $this->properties->color);
        $this->assertEquals($arrayData['movementCost'], $this->properties->movementCost);
        $this->assertEquals($arrayData['defense'], $this->properties->defenseBonus);
        $this->assertEquals($arrayData['resources'], $this->properties->resourceYield);
    }

    public function testValueEquality(): void
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
        $this->assertEquals($properties1->name, $properties2->name);
        $this->assertEquals($properties1->toArray(), $properties2->toArray());
        $this->assertEquals($properties1->toDetailedArray(), $properties2->toDetailedArray());
    }

    public function testBackwardCompatibilityWithQuickAccessors(): void
    {
        $arrayData = $this->properties->toArray();
        
        // Property hooks should match array values
        $this->assertEquals($arrayData['name'], $this->properties->name);
        $this->assertEquals($arrayData['color'], $this->properties->color);
        $this->assertEquals($arrayData['movementCost'], $this->properties->movementCost);
        $this->assertEquals($arrayData['defense'], $this->properties->defenseBonus);
        $this->assertEquals($arrayData['resources'], $this->properties->resourceYield);
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
        // Water should be impassable
        $impassable = TerrainType::WATER->getProperties();
        $this->assertFalse($impassable->isPassable);
        
        // Plains should be passable  
        $this->assertTrue($this->properties->isPassable);
    }

    #[DataProvider('terrainTypeBasicProvider')]
    public function testDifferentTerrainTypesBasicProperties(
        TerrainType $terrainType, 
        bool $expectedPassable
    ): void {
        $properties = $terrainType->getProperties();
        
        $this->assertEquals($expectedPassable, $properties->isPassable);
        $this->assertIsString($properties->name);
        $this->assertIsInt($properties->color);
        $this->assertGreaterThanOrEqual(0, $properties->movementCost);
        $this->assertGreaterThanOrEqual(0, $properties->defenseBonus);
        $this->assertGreaterThanOrEqual(0, $properties->resourceYield);
    }

    public static function terrainTypeBasicProvider(): array
    {
        return [
            'Plains' => [TerrainType::PLAINS, true],
            'Forest' => [TerrainType::FOREST, true],
            'Mountain' => [TerrainType::MOUNTAIN, true],
            'Water' => [TerrainType::WATER, false],
            'Desert' => [TerrainType::DESERT, true],
            'Swamp' => [TerrainType::SWAMP, true],
        ];
    }

    public function testComponentConsistency(): void
    {
        // Test that component properties are accessible both through hooks and component objects
        $this->assertEquals($this->properties->name, $this->properties->visual->name);
        $this->assertEquals($this->properties->color, $this->properties->visual->color);
        $this->assertEquals($this->properties->movementCost, $this->properties->movement->movementCost);
        $this->assertEquals($this->properties->defenseBonus, $this->properties->combat->defenseBonus);
        $this->assertEquals($this->properties->resourceYield, $this->properties->economic->resourceYield);
        $this->assertEquals($this->properties->isPassable, $this->properties->movement->isPassable());
    }

    public function testHexColorFormatting(): void
    {
        $hexColor = $this->properties->hexColor;
        
        $this->assertStringStartsWith('#', $hexColor);
        $this->assertEquals(7, strlen($hexColor)); // # + 6 hex digits
        $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/i', $hexColor);
    }
} 