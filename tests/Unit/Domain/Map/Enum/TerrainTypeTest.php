<?php

namespace App\Tests\Unit\Domain\Map\Enum;

use App\Domain\Map\Enum\TerrainType;
use App\Domain\Map\ValueObject\TerrainProperties;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TerrainType enum and its new Value Object structure
 */
class TerrainTypeTest extends TestCase
{
    public function testPlainsHasCorrectProperties(): void
    {
        $properties = TerrainType::PLAINS->getProperties();
        
        $this->assertInstanceOf(TerrainProperties::class, $properties);
        $this->assertEquals('Plains', $properties->name);
        $this->assertEquals(0x90EE90, $properties->color);
        $this->assertEquals(1, $properties->movementCost);
        $this->assertEquals(1, $properties->defenseBonus);
        $this->assertEquals(2, $properties->resourceYield);
    }

    public function testForestHasCorrectProperties(): void
    {
        $properties = TerrainType::FOREST->getProperties();
        
        $this->assertInstanceOf(TerrainProperties::class, $properties);
        $this->assertEquals('Forest', $properties->name);
        $this->assertEquals(0x228B22, $properties->color);
        $this->assertEquals(2, $properties->movementCost);
        $this->assertEquals(3, $properties->defenseBonus);
        $this->assertEquals(3, $properties->resourceYield);
    }

    public function testMountainHasCorrectProperties(): void
    {
        $properties = TerrainType::MOUNTAIN->getProperties();
        
        $this->assertInstanceOf(TerrainProperties::class, $properties);
        $this->assertEquals('Mountain', $properties->name);
        $this->assertEquals(0x808080, $properties->color);
        $this->assertEquals(3, $properties->movementCost);
        $this->assertEquals(4, $properties->defenseBonus);
        $this->assertEquals(4, $properties->resourceYield);
    }

    public function testWaterHasCorrectProperties(): void
    {
        $properties = TerrainType::WATER->getProperties();
        
        $this->assertInstanceOf(TerrainProperties::class, $properties);
        $this->assertEquals('Water', $properties->name);
        $this->assertEquals(0x4169E1, $properties->color);
        $this->assertEquals(0, $properties->movementCost); // Impassable
        $this->assertEquals(0, $properties->defenseBonus);
        $this->assertEquals(1, $properties->resourceYield);
        $this->assertFalse($properties->isPassable);
    }

    public function testDesertHasCorrectProperties(): void
    {
        $properties = TerrainType::DESERT->getProperties();
        
        $this->assertInstanceOf(TerrainProperties::class, $properties);
        $this->assertEquals('Desert', $properties->name);
        $this->assertEquals(0xF4A460, $properties->color);
        $this->assertEquals(2, $properties->movementCost);
        $this->assertEquals(1, $properties->defenseBonus);
        $this->assertEquals(1, $properties->resourceYield);
    }

    public function testSwampHasCorrectProperties(): void
    {
        $properties = TerrainType::SWAMP->getProperties();
        
        $this->assertInstanceOf(TerrainProperties::class, $properties);
        $this->assertEquals('Swamp', $properties->name);
        $this->assertEquals(0x556B2F, $properties->color);
        $this->assertEquals(3, $properties->movementCost);
        $this->assertEquals(2, $properties->defenseBonus);
        $this->assertEquals(2, $properties->resourceYield);
    }

    public function testAllTerrainTypesReturnTerrainPropertiesObject(): void
    {
        foreach (TerrainType::cases() as $terrainType) {
            $properties = $terrainType->getProperties();
            $this->assertInstanceOf(TerrainProperties::class, $properties);
        }
    }

    public function testAllTerrainTypesHaveValidValues(): void
    {
        foreach (TerrainType::cases() as $terrainType) {
            $properties = $terrainType->getProperties();
            
            // Name should be non-empty string
            $this->assertIsString($properties->name);
            $this->assertNotEmpty($properties->name);
            
            // Color should be valid hex value
            $this->assertIsInt($properties->color);
            $this->assertGreaterThanOrEqual(0, $properties->color);
            $this->assertLessThanOrEqual(0xFFFFFF, $properties->color);
            
            // Movement cost should be non-negative
            $this->assertIsInt($properties->movementCost);
            $this->assertGreaterThanOrEqual(0, $properties->movementCost);
            
            // Defense should be non-negative
            $this->assertIsInt($properties->defenseBonus);
            $this->assertGreaterThanOrEqual(0, $properties->defenseBonus);
            
            // Resources should be positive
            $this->assertIsInt($properties->resourceYield);
            $this->assertGreaterThan(0, $properties->resourceYield);
        }
    }

    public function testWaterIsOnlyImpassableTerrain(): void
    {
        $impassableTerrains = [];
        
        foreach (TerrainType::cases() as $terrainType) {
            if (!$terrainType->getProperties()->isPassable) {
                $impassableTerrains[] = $terrainType;
            }
        }
        
        $this->assertCount(1, $impassableTerrains, 'Only water should be impassable');
        $this->assertEquals(TerrainType::WATER, $impassableTerrains[0]);
    }

    public function testTerrainTypesCanBeCreatedFromString(): void
    {
        $this->assertEquals(TerrainType::PLAINS, TerrainType::from('plains'));
        $this->assertEquals(TerrainType::FOREST, TerrainType::from('forest'));
        $this->assertEquals(TerrainType::MOUNTAIN, TerrainType::from('mountain'));
        $this->assertEquals(TerrainType::WATER, TerrainType::from('water'));
        $this->assertEquals(TerrainType::DESERT, TerrainType::from('desert'));
        $this->assertEquals(TerrainType::SWAMP, TerrainType::from('swamp'));
    }

    public function testAllTerrainTypesHaveUniqueColors(): void
    {
        $colors = [];
        
        foreach (TerrainType::cases() as $terrainType) {
            $color = $terrainType->getProperties()->color;
            
            $this->assertNotContains($color, $colors, "Color {$color} is used by multiple terrain types");
            $colors[] = $color;
        }
    }

    public function testMountainHasHighestDefense(): void
    {
        $maxDefense = 0;
        $maxDefenseTerrain = null;
        
        foreach (TerrainType::cases() as $terrainType) {
            $defense = $terrainType->getProperties()->defenseBonus;
            
            if ($defense > $maxDefense) {
                $maxDefense = $defense;
                $maxDefenseTerrain = $terrainType;
            }
        }
        
        $this->assertEquals(TerrainType::MOUNTAIN, $maxDefenseTerrain);
        $this->assertEquals(4, $maxDefense);
    }

    public function testPlainsHasLowestMovementCost(): void
    {
        $minMovementCost = PHP_INT_MAX;
        $minMovementTerrain = null;
        
        foreach (TerrainType::cases() as $terrainType) {
            $movementCost = $terrainType->getProperties()->movementCost;
            
            // Skip impassable terrain (movement cost 0)
            if ($movementCost > 0 && $movementCost < $minMovementCost) {
                $minMovementCost = $movementCost;
                $minMovementTerrain = $terrainType;
            }
        }
        
        $this->assertEquals(TerrainType::PLAINS, $minMovementTerrain);
        $this->assertEquals(1, $minMovementCost);
    }

    public function testPropertiesConsistency(): void
    {
        foreach (TerrainType::cases() as $terrainType) {
            $properties = $terrainType->getProperties();

            $this->assertIsString($properties->name);
            $this->assertIsInt($properties->color);
            $this->assertIsInt($properties->movementCost);
            $this->assertIsInt($properties->defenseBonus);
            $this->assertIsInt($properties->resourceYield);

            // Test structured array format
            $arrayProperties = $properties->toArray();
            $this->assertIsArray($arrayProperties);
            $this->assertArrayHasKey('name', $arrayProperties);
            $this->assertArrayHasKey('color', $arrayProperties);
            $this->assertArrayHasKey('movementCost', $arrayProperties);
            $this->assertArrayHasKey('defense', $arrayProperties);
            $this->assertArrayHasKey('resources', $arrayProperties);

            // Verify consistency between properties and array format
            $this->assertEquals($properties->name, $arrayProperties['name']);
            $this->assertEquals($properties->color, $arrayProperties['color']);
            $this->assertEquals($properties->movementCost, $arrayProperties['movementCost']);
            $this->assertEquals($properties->defenseBonus, $arrayProperties['defense']);
            $this->assertEquals($properties->resourceYield, $arrayProperties['resources']);
        }
    }

    public function testSpecializedPropertyAccessors(): void
    {
        $forest = TerrainType::FOREST;
        
        // Test individual property accessor methods through getProperties()
        $this->assertInstanceOf(\App\Domain\Map\ValueObject\TerrainVisualProperties::class, $forest->getProperties()->visual());
        $this->assertInstanceOf(\App\Domain\Map\ValueObject\TerrainMovementProperties::class, $forest->getProperties()->movement());
        $this->assertInstanceOf(\App\Domain\Map\ValueObject\TerrainCombatProperties::class, $forest->getProperties()->combat());
        $this->assertInstanceOf(\App\Domain\Map\ValueObject\TerrainEconomicProperties::class, $forest->getProperties()->economic());
        
        // Test quick access methods through getProperties()
        $this->assertEquals('Forest', $forest->getProperties()->name);
        $this->assertEquals(0x228B22, $forest->getProperties()->color);
        $this->assertEquals(2, $forest->getProperties()->movementCost);
        $this->assertTrue($forest->getProperties()->isPassable);
        $this->assertEquals(3, $forest->getProperties()->defenseBonus);
        $this->assertEquals(3, $forest->getProperties()->resourceYield);
    }

    public function testMovementPropertySpecificMethods(): void
    {
        // Test passability through getProperties()
        $this->assertTrue(TerrainType::PLAINS->getProperties()->isPassable);
        $this->assertFalse(TerrainType::WATER->getProperties()->isPassable);
        
        // Test through specialized movement properties
        $this->assertTrue(TerrainType::PLAINS->getProperties()->movement()->isEasyToTraverse());
        $this->assertTrue(TerrainType::SWAMP->getProperties()->movement()->isDifficultToTraverse());
        $this->assertTrue(TerrainType::WATER->getProperties()->movement()->isImpassable());
    }
} 