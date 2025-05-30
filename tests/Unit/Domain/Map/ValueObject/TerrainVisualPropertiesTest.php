<?php

namespace Tests\Unit\Domain\Map\ValueObject;

use App\Domain\Map\ValueObject\TerrainVisualProperties;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for TerrainVisualProperties Value Object
 */
class TerrainVisualPropertiesTest extends TestCase
{
    public function testCreateTerrainVisualProperties(): void
    {
        $properties = new TerrainVisualProperties('Forest', 0x228B22);
        
        $this->assertEquals('Forest', $properties->getName());
        $this->assertEquals(0x228B22, $properties->getColor());
    }

    public function testGetHexColor(): void
    {
        $properties = new TerrainVisualProperties('Plains', 0x90EE90);
        
        $this->assertEquals('#90EE90', $properties->getHexColor());
    }

    public function testGetHexColorWithPadding(): void
    {
        // Test color that needs padding (like 0x000001)
        $properties = new TerrainVisualProperties('Dark', 0x000001);
        
        $this->assertEquals('#000001', $properties->getHexColor());
    }

    public function testGetHexColorWithZero(): void
    {
        $properties = new TerrainVisualProperties('Black', 0x000000);
        
        $this->assertEquals('#000000', $properties->getHexColor());
    }

    public function testGetHexColorWithMaxValue(): void
    {
        $properties = new TerrainVisualProperties('White', 0xFFFFFF);
        
        $this->assertEquals('#FFFFFF', $properties->getHexColor());
    }

    public function testToArray(): void
    {
        $properties = new TerrainVisualProperties('Mountain', 0x808080);
        $array = $properties->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('color', $array);
        $this->assertArrayHasKey('hexColor', $array);
        
        $this->assertEquals('Mountain', $array['name']);
        $this->assertEquals(0x808080, $array['color']);
        $this->assertEquals('#808080', $array['hexColor']);
    }

    public function testReadonlyProperty(): void
    {
        $properties = new TerrainVisualProperties('Test', 0x123456);
        
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
        
        $this->assertFalse($hasSetters, 'TerrainVisualProperties should not have public setters');
    }

    public function testValueObjectImmutability(): void
    {
        $properties1 = new TerrainVisualProperties('Forest', 0x228B22);
        $properties2 = new TerrainVisualProperties('Forest', 0x228B22);
        
        // Same values should create equivalent objects
        $this->assertEquals($properties1->getName(), $properties2->getName());
        $this->assertEquals($properties1->getColor(), $properties2->getColor());
        $this->assertEquals($properties1->getHexColor(), $properties2->getHexColor());
        $this->assertEquals($properties1->toArray(), $properties2->toArray());
    }

    public function testDifferentValuesCreateDifferentObjects(): void
    {
        $properties1 = new TerrainVisualProperties('Forest', 0x228B22);
        $properties2 = new TerrainVisualProperties('Plains', 0x90EE90);
        
        $this->assertNotEquals($properties1->getName(), $properties2->getName());
        $this->assertNotEquals($properties1->getColor(), $properties2->getColor());
        $this->assertNotEquals($properties1->getHexColor(), $properties2->getHexColor());
        $this->assertNotEquals($properties1->toArray(), $properties2->toArray());
    }

    #[DataProvider('validColorProvider')]
    public function testValidColors(int $color, string $expectedHex): void
    {
        $properties = new TerrainVisualProperties('Test', $color);
        
        $this->assertEquals($color, $properties->getColor());
        $this->assertEquals($expectedHex, $properties->getHexColor());
    }

    #[DataProvider('validNameProvider')]
    public function testValidNames(string $name): void
    {
        $properties = new TerrainVisualProperties($name, 0x000000);
        
        $this->assertEquals($name, $properties->getName());
    }

    public static function validColorProvider(): array
    {
        return [
            'Black' => [0x000000, '#000000'],
            'White' => [0xFFFFFF, '#FFFFFF'],
            'Red' => [0xFF0000, '#FF0000'],
            'Green' => [0x00FF00, '#00FF00'],
            'Blue' => [0x0000FF, '#0000FF'],
            'Forest Green' => [0x228B22, '#228B22'],
            'Light Green' => [0x90EE90, '#90EE90'],
            'Gray' => [0x808080, '#808080'],
            'Royal Blue' => [0x4169E1, '#4169E1'],
            'Sandy Brown' => [0xF4A460, '#F4A460'],
            'Dark Olive Green' => [0x556B2F, '#556B2F'],
        ];
    }

    public static function validNameProvider(): array
    {
        return [
            'Single word' => ['Forest'],
            'Multiple words' => ['Dark Forest'],
            'With numbers' => ['Area 51'],
            'With special chars' => ['Forest-Land'],
            'Mixed case' => ['MiXeD CaSe'],
            'Short name' => ['A'],
            'Long name' => ['Very Long Terrain Name With Many Words'],
        ];
    }
} 