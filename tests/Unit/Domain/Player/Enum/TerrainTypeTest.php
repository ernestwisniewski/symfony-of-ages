<?php

namespace App\Tests\Unit\Domain\Player\Enum;

use App\Domain\Player\Enum\TerrainType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TerrainType enum
 */
class TerrainTypeTest extends TestCase
{
    public function testPlainsHasCorrectProperties(): void
    {
        $properties = TerrainType::PLAINS->getProperties();
        
        $this->assertEquals('Plains', $properties['name']);
        $this->assertEquals(0x90EE90, $properties['color']);
        $this->assertEquals(1, $properties['movementCost']);
        $this->assertEquals(1, $properties['defense']);
        $this->assertEquals(2, $properties['resources']);
    }

    public function testForestHasCorrectProperties(): void
    {
        $properties = TerrainType::FOREST->getProperties();
        
        $this->assertEquals('Forest', $properties['name']);
        $this->assertEquals(0x228B22, $properties['color']);
        $this->assertEquals(2, $properties['movementCost']);
        $this->assertEquals(3, $properties['defense']);
        $this->assertEquals(3, $properties['resources']);
    }

    public function testMountainHasCorrectProperties(): void
    {
        $properties = TerrainType::MOUNTAIN->getProperties();
        
        $this->assertEquals('Mountain', $properties['name']);
        $this->assertEquals(0x808080, $properties['color']);
        $this->assertEquals(3, $properties['movementCost']);
        $this->assertEquals(4, $properties['defense']);
        $this->assertEquals(4, $properties['resources']);
    }

    public function testWaterHasCorrectProperties(): void
    {
        $properties = TerrainType::WATER->getProperties();
        
        $this->assertEquals('Water', $properties['name']);
        $this->assertEquals(0x4169E1, $properties['color']);
        $this->assertEquals(0, $properties['movementCost']); // Impassable
        $this->assertEquals(0, $properties['defense']);
        $this->assertEquals(1, $properties['resources']);
    }

    public function testDesertHasCorrectProperties(): void
    {
        $properties = TerrainType::DESERT->getProperties();
        
        $this->assertEquals('Desert', $properties['name']);
        $this->assertEquals(0xF4A460, $properties['color']);
        $this->assertEquals(2, $properties['movementCost']);
        $this->assertEquals(1, $properties['defense']);
        $this->assertEquals(1, $properties['resources']);
    }

    public function testSwampHasCorrectProperties(): void
    {
        $properties = TerrainType::SWAMP->getProperties();
        
        $this->assertEquals('Swamp', $properties['name']);
        $this->assertEquals(0x556B2F, $properties['color']);
        $this->assertEquals(3, $properties['movementCost']);
        $this->assertEquals(2, $properties['defense']);
        $this->assertEquals(2, $properties['resources']);
    }

    public function testAllTerrainTypesHaveRequiredProperties(): void
    {
        $requiredKeys = ['name', 'color', 'movementCost', 'defense', 'resources'];
        
        foreach (TerrainType::cases() as $terrainType) {
            $properties = $terrainType->getProperties();
            
            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey($key, $properties, "Terrain {$terrainType->value} missing property: {$key}");
            }
        }
    }

    public function testAllTerrainTypesHaveValidValues(): void
    {
        foreach (TerrainType::cases() as $terrainType) {
            $properties = $terrainType->getProperties();
            
            // Name should be non-empty string
            $this->assertIsString($properties['name']);
            $this->assertNotEmpty($properties['name']);
            
            // Color should be valid hex value
            $this->assertIsInt($properties['color']);
            $this->assertGreaterThanOrEqual(0, $properties['color']);
            $this->assertLessThanOrEqual(0xFFFFFF, $properties['color']);
            
            // Movement cost should be non-negative
            $this->assertIsInt($properties['movementCost']);
            $this->assertGreaterThanOrEqual(0, $properties['movementCost']);
            
            // Defense should be non-negative
            $this->assertIsInt($properties['defense']);
            $this->assertGreaterThanOrEqual(0, $properties['defense']);
            
            // Resources should be positive
            $this->assertIsInt($properties['resources']);
            $this->assertGreaterThan(0, $properties['resources']);
        }
    }

    public function testWaterIsOnlyImpassableTerrain(): void
    {
        $impassableTerrains = [];
        
        foreach (TerrainType::cases() as $terrainType) {
            $properties = $terrainType->getProperties();
            
            if ($properties['movementCost'] === 0) {
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
            $color = $terrainType->getProperties()['color'];
            
            $this->assertNotContains($color, $colors, "Color {$color} is used by multiple terrain types");
            $colors[] = $color;
        }
    }

    public function testMountainHasHighestDefense(): void
    {
        $maxDefense = 0;
        $maxDefenseTerrain = null;
        
        foreach (TerrainType::cases() as $terrainType) {
            $defense = $terrainType->getProperties()['defense'];
            
            if ($defense > $maxDefense) {
                $maxDefense = $defense;
                $maxDefenseTerrain = $terrainType;
            }
        }
        
        $this->assertEquals(TerrainType::MOUNTAIN, $maxDefenseTerrain);
    }

    public function testPlainsHasLowestMovementCost(): void
    {
        $minMovementCost = PHP_INT_MAX;
        $minMovementTerrain = null;
        
        foreach (TerrainType::cases() as $terrainType) {
            $movementCost = $terrainType->getProperties()['movementCost'];
            
            // Skip impassable terrain (movement cost 0)
            if ($movementCost > 0 && $movementCost < $minMovementCost) {
                $minMovementCost = $movementCost;
                $minMovementTerrain = $terrainType;
            }
        }
        
        $this->assertEquals(TerrainType::PLAINS, $minMovementTerrain);
        $this->assertEquals(1, $minMovementCost);
    }
} 