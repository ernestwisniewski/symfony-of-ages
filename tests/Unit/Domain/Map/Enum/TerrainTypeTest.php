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
        $this->assertEquals('Plains', $properties->getName());
        $this->assertEquals(0x90EE90, $properties->getColor());
        $this->assertEquals(1, $properties->getMovementCost());
        $this->assertEquals(1, $properties->getDefenseBonus());
        $this->assertEquals(2, $properties->getResourceYield());
    }

    public function testForestHasCorrectProperties(): void
    {
        $properties = TerrainType::FOREST->getProperties();
        
        $this->assertInstanceOf(TerrainProperties::class, $properties);
        $this->assertEquals('Forest', $properties->getName());
        $this->assertEquals(0x228B22, $properties->getColor());
        $this->assertEquals(2, $properties->getMovementCost());
        $this->assertEquals(3, $properties->getDefenseBonus());
        $this->assertEquals(3, $properties->getResourceYield());
    }

    public function testMountainHasCorrectProperties(): void
    {
        $properties = TerrainType::MOUNTAIN->getProperties();
        
        $this->assertInstanceOf(TerrainProperties::class, $properties);
        $this->assertEquals('Mountain', $properties->getName());
        $this->assertEquals(0x808080, $properties->getColor());
        $this->assertEquals(3, $properties->getMovementCost());
        $this->assertEquals(4, $properties->getDefenseBonus());
        $this->assertEquals(4, $properties->getResourceYield());
    }

    public function testWaterHasCorrectProperties(): void
    {
        $properties = TerrainType::WATER->getProperties();
        
        $this->assertInstanceOf(TerrainProperties::class, $properties);
        $this->assertEquals('Water', $properties->getName());
        $this->assertEquals(0x4169E1, $properties->getColor());
        $this->assertEquals(0, $properties->getMovementCost()); // Impassable
        $this->assertEquals(0, $properties->getDefenseBonus());
        $this->assertEquals(1, $properties->getResourceYield());
        $this->assertFalse($properties->isPassable());
    }

    public function testDesertHasCorrectProperties(): void
    {
        $properties = TerrainType::DESERT->getProperties();
        
        $this->assertInstanceOf(TerrainProperties::class, $properties);
        $this->assertEquals('Desert', $properties->getName());
        $this->assertEquals(0xF4A460, $properties->getColor());
        $this->assertEquals(2, $properties->getMovementCost());
        $this->assertEquals(1, $properties->getDefenseBonus());
        $this->assertEquals(1, $properties->getResourceYield());
    }

    public function testSwampHasCorrectProperties(): void
    {
        $properties = TerrainType::SWAMP->getProperties();
        
        $this->assertInstanceOf(TerrainProperties::class, $properties);
        $this->assertEquals('Swamp', $properties->getName());
        $this->assertEquals(0x556B2F, $properties->getColor());
        $this->assertEquals(3, $properties->getMovementCost());
        $this->assertEquals(2, $properties->getDefenseBonus());
        $this->assertEquals(2, $properties->getResourceYield());
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
            $this->assertIsString($properties->getName());
            $this->assertNotEmpty($properties->getName());
            
            // Color should be valid hex value
            $this->assertIsInt($properties->getColor());
            $this->assertGreaterThanOrEqual(0, $properties->getColor());
            $this->assertLessThanOrEqual(0xFFFFFF, $properties->getColor());
            
            // Movement cost should be non-negative
            $this->assertIsInt($properties->getMovementCost());
            $this->assertGreaterThanOrEqual(0, $properties->getMovementCost());
            
            // Defense should be non-negative
            $this->assertIsInt($properties->getDefenseBonus());
            $this->assertGreaterThanOrEqual(0, $properties->getDefenseBonus());
            
            // Resources should be positive
            $this->assertIsInt($properties->getResourceYield());
            $this->assertGreaterThan(0, $properties->getResourceYield());
        }
    }

    public function testWaterIsOnlyImpassableTerrain(): void
    {
        $impassableTerrains = [];
        
        foreach (TerrainType::cases() as $terrainType) {
            if (!$terrainType->isPassable()) {
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
            $color = $terrainType->getColor();
            
            $this->assertNotContains($color, $colors, "Color {$color} is used by multiple terrain types");
            $colors[] = $color;
        }
    }

    public function testMountainHasHighestDefense(): void
    {
        $maxDefense = 0;
        $maxDefenseTerrain = null;
        
        foreach (TerrainType::cases() as $terrainType) {
            $defense = $terrainType->getDefenseBonus();
            
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
            $movementCost = $terrainType->getMovementCost();
            
            // Skip impassable terrain (movement cost 0)
            if ($movementCost > 0 && $movementCost < $minMovementCost) {
                $minMovementCost = $movementCost;
                $minMovementTerrain = $terrainType;
            }
        }
        
        $this->assertEquals(TerrainType::PLAINS, $minMovementTerrain);
        $this->assertEquals(1, $minMovementCost);
    }

    public function testLegacyPropertiesBackwardCompatibility(): void
    {
        foreach (TerrainType::cases() as $terrainType) {
            $legacyProperties = $terrainType->getLegacyProperties();
            $newProperties = $terrainType->getProperties();
            
            $this->assertIsArray($legacyProperties);
            $this->assertArrayHasKey('name', $legacyProperties);
            $this->assertArrayHasKey('color', $legacyProperties);
            $this->assertArrayHasKey('movementCost', $legacyProperties);
            $this->assertArrayHasKey('defense', $legacyProperties);
            $this->assertArrayHasKey('resources', $legacyProperties);
            
            // Verify compatibility
            $this->assertEquals($newProperties->getName(), $legacyProperties['name']);
            $this->assertEquals($newProperties->getColor(), $legacyProperties['color']);
            $this->assertEquals($newProperties->getMovementCost(), $legacyProperties['movementCost']);
            $this->assertEquals($newProperties->getDefenseBonus(), $legacyProperties['defense']);
            $this->assertEquals($newProperties->getResourceYield(), $legacyProperties['resources']);
        }
    }

    public function testSpecializedPropertyAccessors(): void
    {
        $forest = TerrainType::FOREST;
        
        // Test individual property accessor methods
        $this->assertInstanceOf(\App\Domain\Map\ValueObject\TerrainVisualProperties::class, $forest->getVisualProperties());
        $this->assertInstanceOf(\App\Domain\Map\ValueObject\TerrainMovementProperties::class, $forest->getMovementProperties());
        $this->assertInstanceOf(\App\Domain\Map\ValueObject\TerrainCombatProperties::class, $forest->getCombatProperties());
        $this->assertInstanceOf(\App\Domain\Map\ValueObject\TerrainEconomicProperties::class, $forest->getEconomicProperties());
        
        // Test quick access methods
        $this->assertEquals('Forest', $forest->getName());
        $this->assertEquals(0x228B22, $forest->getColor());
        $this->assertEquals(2, $forest->getMovementCost());
        $this->assertTrue($forest->isPassable());
        $this->assertEquals(3, $forest->getDefenseBonus());
        $this->assertEquals(3, $forest->getResourceYield());
    }

    public function testTacticalAnalysisMethods(): void
    {
        // Test strategic importance
        $this->assertTrue(TerrainType::MOUNTAIN->isStrategicallyImportant());
        $this->assertTrue(TerrainType::FOREST->isStrategicallyImportant());
        $this->assertFalse(TerrainType::WATER->isStrategicallyImportant());
        
        // Test tactical advantage
        $this->assertTrue(TerrainType::MOUNTAIN->providesTacticalAdvantage());
        $this->assertTrue(TerrainType::FOREST->providesTacticalAdvantage());
        $this->assertFalse(TerrainType::PLAINS->providesTacticalAdvantage());
        
        // Test economic viability
        $this->assertTrue(TerrainType::MOUNTAIN->isEconomicallyViable());
        $this->assertTrue(TerrainType::FOREST->isEconomicallyViable());
        $this->assertFalse(TerrainType::DESERT->isEconomicallyViable());
    }

    public function testMovementPropertySpecificMethods(): void
    {
        // Test passability
        $this->assertTrue(TerrainType::PLAINS->isPassable());
        $this->assertFalse(TerrainType::WATER->isPassable());
        
        // Test through specialized movement properties
        $this->assertTrue(TerrainType::PLAINS->getMovementProperties()->isEasyToTraverse());
        $this->assertTrue(TerrainType::SWAMP->getMovementProperties()->isDifficultToTraverse());
        $this->assertTrue(TerrainType::WATER->getMovementProperties()->isImpassable());
    }
} 