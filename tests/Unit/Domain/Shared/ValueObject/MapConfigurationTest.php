<?php

namespace Tests\Unit\Domain\Shared\ValueObject;

use App\Domain\Shared\ValueObject\MapConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MapConfiguration value object
 */
class MapConfigurationTest extends TestCase
{
    public function testGetConfigReturnsDefaultConfiguration(): void
    {
        $config = MapConfiguration::getConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('rows', $config);
        $this->assertArrayHasKey('cols', $config);
        $this->assertArrayHasKey('size', $config);

        $this->assertEquals(MapConfiguration::ROWS, $config['rows']);
        $this->assertEquals(MapConfiguration::COLS, $config['cols']);
        $this->assertEquals(MapConfiguration::HEX_SIZE, $config['size']);
    }

    public function testGetConfigWithAdditionalConfiguration(): void
    {
        $additionalConfig = [
            'expected_players' => 4,
            'difficulty' => 'hard'
        ];

        $config = MapConfiguration::getConfig($additionalConfig);

        $this->assertIsArray($config);
        
        // Check default values are present
        $this->assertEquals(MapConfiguration::ROWS, $config['rows']);
        $this->assertEquals(MapConfiguration::COLS, $config['cols']);
        
        // Check additional config is merged
        $this->assertEquals(4, $config['expected_players']);
        $this->assertEquals('hard', $config['difficulty']);
    }

    public function testGetConfigMergesCorrectly(): void
    {
        $additionalConfig = [
            'rows' => 120, // Should override default
            'custom_setting' => 'value'
        ];

        $config = MapConfiguration::getConfig($additionalConfig);

        // Additional config should override defaults
        $this->assertEquals(120, $config['rows']);
        // But other defaults should remain
        $this->assertEquals(MapConfiguration::COLS, $config['cols']);
        // And additional config should be present
        $this->assertEquals('value', $config['custom_setting']);
    }

    public function testGetTotalTiles(): void
    {
        $totalTiles = MapConfiguration::getTotalTiles();

        $this->assertIsInt($totalTiles);
        $this->assertEquals(MapConfiguration::ROWS * MapConfiguration::COLS, $totalTiles);
        $this->assertEquals(100 * 100, $totalTiles); // 10000 tiles
    }

    public function testAreCoordinatesValidWithValidCoordinates(): void
    {
        $this->assertTrue(MapConfiguration::areCoordinatesValid(0, 0));
        $this->assertTrue(MapConfiguration::areCoordinatesValid(50, 50));
        $this->assertTrue(MapConfiguration::areCoordinatesValid(99, 99)); // Maximum valid coordinates
    }

    public function testAreCoordinatesValidWithInvalidCoordinates(): void
    {
        // Negative coordinates
        $this->assertFalse(MapConfiguration::areCoordinatesValid(-1, 0));
        $this->assertFalse(MapConfiguration::areCoordinatesValid(0, -1));
        $this->assertFalse(MapConfiguration::areCoordinatesValid(-1, -1));

        // Out of bounds coordinates
        $this->assertFalse(MapConfiguration::areCoordinatesValid(100, 50)); // Row too high
        $this->assertFalse(MapConfiguration::areCoordinatesValid(50, 100)); // Col too high
        $this->assertFalse(MapConfiguration::areCoordinatesValid(100, 100)); // Both too high
        
        // Way out of bounds
        $this->assertFalse(MapConfiguration::areCoordinatesValid(200, 200));
    }

    public function testAreCoordinatesValidWithBoundaryValues(): void
    {
        // Test all four corners
        $this->assertTrue(MapConfiguration::areCoordinatesValid(0, 0)); // Top-left
        $this->assertTrue(MapConfiguration::areCoordinatesValid(0, 99)); // Top-right
        $this->assertTrue(MapConfiguration::areCoordinatesValid(99, 0)); // Bottom-left
        $this->assertTrue(MapConfiguration::areCoordinatesValid(99, 99)); // Bottom-right

        // Test just outside boundaries
        $this->assertFalse(MapConfiguration::areCoordinatesValid(0, 100)); // Just outside right
        $this->assertFalse(MapConfiguration::areCoordinatesValid(100, 0)); // Just outside bottom
    }

    public function testMapConfigurationConstants(): void
    {
        // Test that constants have expected values
        $this->assertEquals(100, MapConfiguration::ROWS);
        $this->assertEquals(100, MapConfiguration::COLS);
        $this->assertEquals(58, MapConfiguration::HEX_SIZE);
        
        // Test that they are proper integers
        $this->assertIsInt(MapConfiguration::ROWS);
        $this->assertIsInt(MapConfiguration::COLS);
        $this->assertIsInt(MapConfiguration::HEX_SIZE);
        
        // Test reasonable values
        $this->assertGreaterThan(0, MapConfiguration::ROWS);
        $this->assertGreaterThan(0, MapConfiguration::COLS);
        $this->assertGreaterThan(0, MapConfiguration::HEX_SIZE);
    }

    public function testGetConfigDoesNotModifyOriginalArray(): void
    {
        $originalConfig = [
            'test_key' => 'original_value'
        ];
        
        $config = MapConfiguration::getConfig($originalConfig);
        
        // Modify returned config
        $config['test_key'] = 'modified_value';
        $config['new_key'] = 'new_value';
        
        // Original should be unchanged
        $this->assertEquals('original_value', $originalConfig['test_key']);
        $this->assertArrayNotHasKey('new_key', $originalConfig);
    }

    public function testGetConfigWithEmptyAdditionalConfig(): void
    {
        $config1 = MapConfiguration::getConfig();
        $config2 = MapConfiguration::getConfig([]);

        $this->assertEquals($config1, $config2);
    }

    public function testTotalTilesCalculation(): void
    {
        $manualCalculation = MapConfiguration::ROWS * MapConfiguration::COLS;
        $methodCalculation = MapConfiguration::getTotalTiles();
        
        $this->assertEquals($manualCalculation, $methodCalculation);
        $this->assertEquals(10000, $methodCalculation); // 100 * 100
    }
} 