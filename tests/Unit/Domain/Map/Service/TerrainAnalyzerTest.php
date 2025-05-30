<?php

namespace Tests\Unit\Domain\Map\Service;

use App\Domain\Map\Service\TerrainAnalyzer;
use App\Domain\Map\ValueObject\TerrainProperties;
use App\Domain\Map\ValueObject\TerrainVisualProperties;
use App\Domain\Map\ValueObject\TerrainMovementProperties;
use App\Domain\Map\ValueObject\TerrainCombatProperties;
use App\Domain\Map\ValueObject\TerrainEconomicProperties;
use App\Domain\Map\Enum\TerrainType;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for TerrainAnalyzer domain service
 */
class TerrainAnalyzerTest extends TestCase
{
    private TerrainAnalyzer $analyzer;
    private TerrainProperties $forestProperties;

    protected function setUp(): void
    {
        $this->analyzer = new TerrainAnalyzer();
        
        // Standard forest terrain for testing
        $this->forestProperties = new TerrainProperties(
            new TerrainVisualProperties('Forest', 0x228B22),
            new TerrainMovementProperties(2),
            new TerrainCombatProperties(3),
            new TerrainEconomicProperties(3)
        );
    }

    public function testCalculateOverallValue(): void
    {
        $value = $this->analyzer->calculateOverallValue($this->forestProperties);
        
        // Expected: (3*2) + 3 + 1 - 2 = 8
        $this->assertEquals(8, $value);
    }

    public function testCalculateOverallValueForImpassableTerrain(): void
    {
        $waterProperties = TerrainType::WATER->getProperties();
        $value = $this->analyzer->calculateOverallValue($waterProperties);
        
        // Water should have low value due to being impassable
        $this->assertGreaterThanOrEqual(0, $value); // Should not be negative
    }

    public function testCalculateTacticalScore(): void
    {
        $score = $this->analyzer->calculateTacticalScore($this->forestProperties);
        
        // Expected: 2.0 (passable) + 0.0 (resource rich but not >= 4) = 2.0
        $this->assertEquals(2.0, $score);
    }

    public function testCalculateTacticalScoreForMountain(): void
    {
        $mountainProperties = TerrainType::MOUNTAIN->getProperties();
        $score = $this->analyzer->calculateTacticalScore($mountainProperties);
        
        // Mountain should have high tactical score due to fortification and resources
        $this->assertGreaterThan(4.0, $score);
    }

    public function testCalculateTacticalScoreForWater(): void
    {
        $waterProperties = TerrainType::WATER->getProperties();
        $score = $this->analyzer->calculateTacticalScore($waterProperties);
        
        // Water should have 0 score (not passable)
        $this->assertEquals(0.0, $score);
    }

    public function testProvidesTotalAdvantage(): void
    {
        $result = $this->analyzer->providesTotalAdvantage($this->forestProperties);
        
        // Forest: tactically advantaged (false, defense=3<4) but economically viable (true, resources=3)
        $this->assertFalse($result);
    }

    public function testProvidesTotalAdvantageForMountain(): void
    {
        $mountainProperties = TerrainType::MOUNTAIN->getProperties();
        $result = $this->analyzer->providesTotalAdvantage($mountainProperties);
        
        // Mountain should provide total advantage (fortified AND resource rich)
        $this->assertTrue($result);
    }

    public function testIsHighValueTarget(): void
    {
        $result = $this->analyzer->isHighValueTarget($this->forestProperties);
        
        // Forest should be high value (strategically important OR resource rich)
        $this->assertTrue($result);
    }

    public function testIsDefensivePosition(): void
    {
        $result = $this->analyzer->isDefensivePosition($this->forestProperties);
        
        // Forest has defense=3, which is >= 2
        $this->assertTrue($result);
    }

    public function testIsDefensivePositionForPlains(): void
    {
        $plainsProperties = TerrainType::PLAINS->getProperties();
        $result = $this->analyzer->isDefensivePosition($plainsProperties);
        
        // Plains have low defense
        $this->assertFalse($result);
    }

    public function testAllowsQuickTraversal(): void
    {
        $plainsProperties = TerrainType::PLAINS->getProperties();
        $result = $this->analyzer->allowsQuickTraversal($plainsProperties);
        
        // Plains: movement cost = 1 and passable
        $this->assertTrue($result);
    }

    public function testAllowsQuickTraversalForForest(): void
    {
        $result = $this->analyzer->allowsQuickTraversal($this->forestProperties);
        
        // Forest: movement cost = 2 (not <= 1)
        $this->assertFalse($result);
    }

    public function testRequiresSpecialMovement(): void
    {
        $mountainProperties = TerrainType::MOUNTAIN->getProperties();
        $result = $this->analyzer->requiresSpecialMovement($mountainProperties);
        
        // Mountain: movement cost = 3 (>= 3)
        $this->assertTrue($result);
    }

    public function testRequiresSpecialMovementForWater(): void
    {
        $waterProperties = TerrainType::WATER->getProperties();
        $result = $this->analyzer->requiresSpecialMovement($waterProperties);
        
        // Water: not passable
        $this->assertTrue($result);
    }

    public function testGetComprehensiveAnalysis(): void
    {
        $analysis = $this->analyzer->getComprehensiveAnalysis($this->forestProperties);
        
        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('overall_value', $analysis);
        $this->assertArrayHasKey('tactical_score', $analysis);
        $this->assertArrayHasKey('strategic_importance', $analysis);
        $this->assertArrayHasKey('tactical_advantages', $analysis);
        $this->assertArrayHasKey('characteristics', $analysis);
        
        // Verify tactical advantages structure
        $this->assertIsArray($analysis['tactical_advantages']);
        $this->assertArrayHasKey('provides_total_advantage', $analysis['tactical_advantages']);
        $this->assertArrayHasKey('is_high_value_target', $analysis['tactical_advantages']);
        $this->assertArrayHasKey('is_defensive_position', $analysis['tactical_advantages']);
        $this->assertArrayHasKey('allows_quick_traversal', $analysis['tactical_advantages']);
        $this->assertArrayHasKey('requires_special_movement', $analysis['tactical_advantages']);
        
        // Verify characteristics structure
        $this->assertIsArray($analysis['characteristics']);
        $this->assertArrayHasKey('tactically_advantaged', $analysis['characteristics']);
        $this->assertArrayHasKey('economically_viable', $analysis['characteristics']);
        $this->assertArrayHasKey('easy_to_traverse', $analysis['characteristics']);
        $this->assertArrayHasKey('difficult_to_traverse', $analysis['characteristics']);
        $this->assertArrayHasKey('fortified', $analysis['characteristics']);
        $this->assertArrayHasKey('resource_rich', $analysis['characteristics']);
    }

    public function testGetMovementDifficultyLevel(): void
    {
        $this->assertEquals('Moderate', $this->analyzer->getMovementDifficultyLevel($this->forestProperties));
        
        $plainsProperties = TerrainType::PLAINS->getProperties();
        $this->assertEquals('Easy', $this->analyzer->getMovementDifficultyLevel($plainsProperties));
        
        $waterProperties = TerrainType::WATER->getProperties();
        $this->assertEquals('Impassable', $this->analyzer->getMovementDifficultyLevel($waterProperties));
        
        $mountainProperties = TerrainType::MOUNTAIN->getProperties();
        $this->assertEquals('Difficult', $this->analyzer->getMovementDifficultyLevel($mountainProperties));
    }

    public function testGetDefensiveLevel(): void
    {
        $this->assertEquals('Strong', $this->analyzer->getDefensiveLevel($this->forestProperties));
        
        $plainsProperties = TerrainType::PLAINS->getProperties();
        $this->assertEquals('Minor', $this->analyzer->getDefensiveLevel($plainsProperties));
        
        $mountainProperties = TerrainType::MOUNTAIN->getProperties();
        $this->assertEquals('Fortress Level 1', $this->analyzer->getDefensiveLevel($mountainProperties));
    }

    public function testGetEconomicLevel(): void
    {
        $this->assertEquals('Rich', $this->analyzer->getEconomicLevel($this->forestProperties));
        
        $plainsProperties = TerrainType::PLAINS->getProperties();
        $this->assertEquals('Moderate', $this->analyzer->getEconomicLevel($plainsProperties));
        
        $waterProperties = TerrainType::WATER->getProperties();
        $this->assertEquals('Poor', $this->analyzer->getEconomicLevel($waterProperties));
    }

    #[DataProvider('terrainTypeAnalysisProvider')]
    public function testDifferentTerrainTypesAnalysis(
        TerrainType $terrainType,
        bool $expectedTacticallyAdvantaged,
        bool $expectedEconomicallyViable,
        bool $expectedStrategicallyImportant
    ): void {
        $properties = $terrainType->getProperties();
        $analysis = $this->analyzer->getComprehensiveAnalysis($properties);
        
        $this->assertEquals($expectedTacticallyAdvantaged, $analysis['characteristics']['tactically_advantaged']);
        $this->assertEquals($expectedEconomicallyViable, $analysis['characteristics']['economically_viable']);
        $this->assertEquals($expectedStrategicallyImportant, $analysis['strategic_importance']);
    }

    public static function terrainTypeAnalysisProvider(): array
    {
        return [
            'Plains' => [TerrainType::PLAINS, false, false, false],
            'Forest' => [TerrainType::FOREST, false, true, true],
            'Mountain' => [TerrainType::MOUNTAIN, true, true, true],
            'Water' => [TerrainType::WATER, false, false, false],
            'Desert' => [TerrainType::DESERT, false, false, false],
            'Swamp' => [TerrainType::SWAMP, false, false, false],
        ];
    }

    #[DataProvider('tacticalScoreProvider')]
    public function testTacticalScoreCalculation(
        TerrainType $terrainType,
        float $expectedMinScore,
        float $expectedMaxScore
    ): void {
        $properties = $terrainType->getProperties();
        $score = $this->analyzer->calculateTacticalScore($properties);
        
        $this->assertGreaterThanOrEqual($expectedMinScore, $score);
        $this->assertLessThanOrEqual($expectedMaxScore, $score);
    }

    public static function tacticalScoreProvider(): array
    {
        return [
            'Plains' => [TerrainType::PLAINS, 3.5, 3.5], // Passable (2.0) + easy traversal (1.5)
            'Forest' => [TerrainType::FOREST, 2.0, 2.0], // Passable (2.0) only
            'Mountain' => [TerrainType::MOUNTAIN, 4.5, 4.5], // Passable (2.0) + fortified (2.0) + resource rich (1.5, since resources=4)
            'Water' => [TerrainType::WATER, 0.0, 0.0], // Not passable
            'Desert' => [TerrainType::DESERT, 2.0, 2.0], // Passable (2.0) only (movement cost = 2, not >= 3)
            'Swamp' => [TerrainType::SWAMP, 1.0, 1.0], // Passable (2.0) - difficult (-1.0)
        ];
    }
} 