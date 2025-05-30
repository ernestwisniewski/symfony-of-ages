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
        
        $this->assertEquals('Forest', $properties->name);
        $this->assertEquals(0x228B22, $properties->color);
    }

    public function testGetHexColor(): void
    {
        $properties = new TerrainVisualProperties('Plains', 0x90EE90);
        
        $this->assertEquals('#90EE90', $properties->getHexColor());
    }

    public function testGetHexColorWithPadding(): void
    {
        $properties = new TerrainVisualProperties('Test', 0x1);
        
        $this->assertEquals('#000001', $properties->getHexColor());
    }

    public function testGetHexColorWithZero(): void
    {
        $properties = new TerrainVisualProperties('Test', 0x0);
        
        $this->assertEquals('#000000', $properties->getHexColor());
    }

    public function testGetHexColorWithMaxValue(): void
    {
        $properties = new TerrainVisualProperties('Test', 0xFFFFFF);
        
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
        $this->assertEquals($properties1->name, $properties2->name);
        $this->assertEquals($properties1->color, $properties2->color);
        $this->assertEquals($properties1->getHexColor(), $properties2->getHexColor());
        $this->assertEquals($properties1->toArray(), $properties2->toArray());
    }

    public function testDifferentValuesCreateDifferentObjects(): void
    {
        $properties1 = new TerrainVisualProperties('Plains', 0x90EE90);
        $properties2 = new TerrainVisualProperties('Forest', 0x228B22);
        
        $this->assertNotEquals($properties1->getHexColor(), $properties2->getHexColor());
    }

    #[DataProvider('validColorProvider')]
    public function testValidColors(string $name, int $color, string $expectedHex): void
    {
        $properties = new TerrainVisualProperties($name, $color);
        
        $this->assertEquals($expectedHex, $properties->getHexColor());
    }

    #[DataProvider('validNameProvider')]
    public function testValidNames(string $name): void
    {
        $properties = new TerrainVisualProperties($name, 0x000000);
        
        $this->assertEquals($name, $properties->name);
    }

    public function testLongNameThrowsException(): void
    {
        $this->expectException(\App\Domain\Map\Exception\InvalidTerrainDataException::class);
        
        new TerrainVisualProperties('Very Long Terrain Name With Many Words', 0x000000);
    }

    public static function validColorProvider(): array
    {
        return [
            'Black' => ['Black', 0x000000, '#000000'],
            'White' => ['White', 0xFFFFFF, '#FFFFFF'],
            'Red' => ['Red', 0xFF0000, '#FF0000'],
            'Green' => ['Green', 0x00FF00, '#00FF00'],
            'Blue' => ['Blue', 0x0000FF, '#0000FF'],
            'Forest Green' => ['Forest Green', 0x228B22, '#228B22'],
            'Light Green' => ['Light Green', 0x90EE90, '#90EE90'],
            'Gray' => ['Gray', 0x808080, '#808080'],
            'Royal Blue' => ['Royal Blue', 0x4169E1, '#4169E1'],
            'Sandy Brown' => ['Sandy Brown', 0xF4A460, '#F4A460'],
            'Dark Olive Green' => ['Dark Olive Green', 0x556B2F, '#556B2F'],
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
            'Medium name' => ['Short Name Here'],
        ];
    }
} 