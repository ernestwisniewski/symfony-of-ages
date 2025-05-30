<?php

namespace Tests\Unit\Domain\Map\ValueObject;

use App\Domain\Map\ValueObject\TerrainEconomicProperties;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for TerrainEconomicProperties Value Object
 */
class TerrainEconomicPropertiesTest extends TestCase
{
    public function testCreateTerrainEconomicProperties(): void
    {
        $properties = new TerrainEconomicProperties(3);
        
        $this->assertEquals(3, $properties->getResourceYield());
    }

    public function testCreateWithZeroResourceYield(): void
    {
        $properties = new TerrainEconomicProperties(0);
        
        $this->assertEquals(0, $properties->getResourceYield());
    }

    public function testCreateWithNegativeResourceYieldThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource yield cannot be negative');
        
        new TerrainEconomicProperties(-1);
    }

    public function testIsResourceRichWithHighYield(): void
    {
        $properties = new TerrainEconomicProperties(3);
        
        $this->assertTrue($properties->isResourceRich());
    }

    public function testIsResourceRichWithVeryHighYield(): void
    {
        $properties = new TerrainEconomicProperties(5);
        
        $this->assertTrue($properties->isResourceRich());
    }

    public function testIsResourceRichWithLowYield(): void
    {
        $properties = new TerrainEconomicProperties(2);
        
        $this->assertFalse($properties->isResourceRich());
    }

    public function testHasModerateResourcesWithExactValue(): void
    {
        $properties = new TerrainEconomicProperties(2);
        
        $this->assertTrue($properties->hasModerateResources());
    }

    public function testHasModerateResourcesWithDifferentValues(): void
    {
        $lowResources = new TerrainEconomicProperties(1);
        $highResources = new TerrainEconomicProperties(3);
        
        $this->assertFalse($lowResources->hasModerateResources());
        $this->assertFalse($highResources->hasModerateResources());
    }

    public function testIsPoorInResourcesWithLowYield(): void
    {
        $properties = new TerrainEconomicProperties(1);
        
        $this->assertTrue($properties->isPoorInResources());
    }

    public function testIsPoorInResourcesWithZeroYield(): void
    {
        $properties = new TerrainEconomicProperties(0);
        
        $this->assertTrue($properties->isPoorInResources());
    }

    public function testIsPoorInResourcesWithHighYield(): void
    {
        $properties = new TerrainEconomicProperties(2);
        
        $this->assertFalse($properties->isPoorInResources());
    }

    public function testHasNoResourcesWithZeroYield(): void
    {
        $properties = new TerrainEconomicProperties(0);
        
        $this->assertTrue($properties->hasNoResources());
    }

    public function testHasNoResourcesWithPositiveYield(): void
    {
        $properties = new TerrainEconomicProperties(1);
        
        $this->assertFalse($properties->hasNoResources());
    }

    public function testIsHighValueWithHighYield(): void
    {
        $properties = new TerrainEconomicProperties(4);
        
        $this->assertTrue($properties->isHighValue());
    }

    public function testIsHighValueWithVeryHighYield(): void
    {
        $properties = new TerrainEconomicProperties(10);
        
        $this->assertTrue($properties->isHighValue());
    }

    public function testIsHighValueWithLowerYield(): void
    {
        $properties = new TerrainEconomicProperties(3);
        
        $this->assertFalse($properties->isHighValue());
    }

    public function testToArrayStructure(): void
    {
        $properties = new TerrainEconomicProperties(3);
        $array = $properties->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('resourceYield', $array);
        $this->assertArrayHasKey('economicValue', $array);
        $this->assertArrayHasKey('worthExploiting', $array);
        
        $this->assertEquals(3, $array['resourceYield']);
        $this->assertEquals('good', $array['economicValue']);
        $this->assertTrue($array['worthExploiting']);
    }

    #[DataProvider('economicValueLevelProvider')]
    public function testEconomicValueLevels(int $resourceYield, string $expectedLevel): void
    {
        $properties = new TerrainEconomicProperties($resourceYield);
        $array = $properties->toArray();
        
        $this->assertEquals($expectedLevel, $array['economicValue']);
    }

    #[DataProvider('economicAssessmentProvider')]
    public function testEconomicAssessmentLogic(
        int $resourceYield,
        bool $expectedResourceRich,
        bool $expectedModerateResources,
        bool $expectedPoorInResources,
        bool $expectedNoResources,
        bool $expectedHighValue
    ): void {
        $properties = new TerrainEconomicProperties($resourceYield);
        
        $this->assertEquals($expectedResourceRich, $properties->isResourceRich());
        $this->assertEquals($expectedModerateResources, $properties->hasModerateResources());
        $this->assertEquals($expectedPoorInResources, $properties->isPoorInResources());
        $this->assertEquals($expectedNoResources, $properties->hasNoResources());
        $this->assertEquals($expectedHighValue, $properties->isHighValue());
    }

    public function testResourceRichAndWorthExploiting(): void
    {
        // Resource rich terrain should always be worth exploiting
        $resourceRich = new TerrainEconomicProperties(3);
        $this->assertTrue($resourceRich->isResourceRich());
        
        $array = $resourceRich->toArray();
        $this->assertTrue($array['worthExploiting']);
        
        // Non-resource rich terrain should not be worth exploiting
        $poorResources = new TerrainEconomicProperties(1);
        $this->assertFalse($poorResources->isResourceRich());
        
        $poorArray = $poorResources->toArray();
        $this->assertFalse($poorArray['worthExploiting']);
    }

    public function testMutuallyExclusiveResourceStates(): void
    {
        // No resources and moderate resources should be mutually exclusive
        $noResources = new TerrainEconomicProperties(0);
        $this->assertTrue($noResources->hasNoResources());
        $this->assertFalse($noResources->hasModerateResources());
        
        // Moderate and resource rich should be mutually exclusive
        $moderateResources = new TerrainEconomicProperties(2);
        $this->assertTrue($moderateResources->hasModerateResources());
        $this->assertFalse($moderateResources->isResourceRich());
    }

    public function testPoorResourcesIncludesNoResources(): void
    {
        // No resources should also be considered poor in resources
        $noResources = new TerrainEconomicProperties(0);
        $this->assertTrue($noResources->hasNoResources());
        $this->assertTrue($noResources->isPoorInResources());
        
        // Low resources should also be considered poor
        $lowResources = new TerrainEconomicProperties(1);
        $this->assertFalse($lowResources->hasNoResources());
        $this->assertTrue($lowResources->isPoorInResources());
    }

    public function testValueObjectImmutability(): void
    {
        $properties1 = new TerrainEconomicProperties(3);
        $properties2 = new TerrainEconomicProperties(3);
        
        // Same values should create equivalent objects
        $this->assertEquals($properties1->getResourceYield(), $properties2->getResourceYield());
        $this->assertEquals($properties1->isResourceRich(), $properties2->isResourceRich());
        $this->assertEquals($properties1->toArray(), $properties2->toArray());
    }

    public function testReadonlyProperty(): void
    {
        $properties = new TerrainEconomicProperties(2);
        
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
        
        $this->assertFalse($hasSetters, 'TerrainEconomicProperties should not have public setters');
    }

    public static function economicValueLevelProvider(): array
    {
        return [
            'Worthless' => [0, 'worthless'],
            'Poor' => [1, 'poor'],
            'Moderate' => [2, 'moderate'],
            'Good' => [3, 'good'],
            'Excellent Level 1' => [4, 'excellent'],
            'Excellent Level 2' => [5, 'excellent'],
            'Exceptional' => [10, 'excellent'],
        ];
    }

    public static function economicAssessmentProvider(): array
    {
        return [
            // [resourceYield, expectedResourceRich, expectedModerateResources, expectedPoorInResources, expectedNoResources, expectedHighValue]
            'No resources' => [0, false, false, true, true, false],
            'Poor resources' => [1, false, false, true, false, false],
            'Moderate resources' => [2, false, true, false, false, false],
            'Good resources' => [3, true, false, false, false, false],
            'Excellent resources' => [4, true, false, false, false, true],
            'Exceptional resources' => [5, true, false, false, false, true],
            'Maximum yield' => [10, true, false, false, false, true],
        ];
    }
} 