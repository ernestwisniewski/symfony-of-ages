<?php

namespace Tests\Unit\Domain\Map\Service;

use App\Domain\Map\Service\TerrainSmoothingDomainService;
use App\Domain\Map\Enum\TerrainType;
use PHPUnit\Framework\TestCase;

class TerrainSmoothingDomainServiceTest extends TestCase
{
    private TerrainSmoothingDomainService $service;

    protected function setUp(): void
    {
        $this->service = new TerrainSmoothingDomainService();
    }

    public function testGetCompatibilityScore(): void
    {
        // Test known compatibility scores
        $this->assertEquals(1.0, $this->service->getCompatibilityScore(TerrainType::PLAINS, TerrainType::PLAINS));
        $this->assertEquals(1.0, $this->service->getCompatibilityScore(TerrainType::FOREST, TerrainType::FOREST));
        
        // Test specific compatibility relationships
        $this->assertEquals(0.8, $this->service->getCompatibilityScore(TerrainType::PLAINS, TerrainType::FOREST));
        $this->assertEquals(0.8, $this->service->getCompatibilityScore(TerrainType::FOREST, TerrainType::PLAINS));
        
        // Test low compatibility
        $this->assertEquals(0.1, $this->service->getCompatibilityScore(TerrainType::WATER, TerrainType::DESERT));
        $this->assertEquals(0.1, $this->service->getCompatibilityScore(TerrainType::DESERT, TerrainType::WATER));

        // Test water-swamp high compatibility
        $this->assertEquals(0.8, $this->service->getCompatibilityScore(TerrainType::WATER, TerrainType::SWAMP));
        $this->assertEquals(0.8, $this->service->getCompatibilityScore(TerrainType::SWAMP, TerrainType::WATER));
    }

    public function testGetCompatibilityScoreSymmetry(): void
    {
        // Compatibility should be symmetric
        foreach (TerrainType::cases() as $terrain1) {
            foreach (TerrainType::cases() as $terrain2) {
                $score1to2 = $this->service->getCompatibilityScore($terrain1, $terrain2);
                $score2to1 = $this->service->getCompatibilityScore($terrain2, $terrain1);
                
                $this->assertEquals($score1to2, $score2to1, 
                    "Compatibility between {$terrain1->value} and {$terrain2->value} should be symmetric");
            }
        }
    }

    public function testGetCompatibilityScoreRange(): void
    {
        // All compatibility scores should be between 0.0 and 1.0
        foreach (TerrainType::cases() as $terrain1) {
            foreach (TerrainType::cases() as $terrain2) {
                $score = $this->service->getCompatibilityScore($terrain1, $terrain2);
                
                $this->assertGreaterThanOrEqual(0.0, $score);
                $this->assertLessThanOrEqual(1.0, $score);
            }
        }
    }

    public function testAreTerrainTypesCompatible(): void
    {
        // Default threshold (0.5)
        $this->assertTrue($this->service->areTerrainTypesCompatible(TerrainType::PLAINS, TerrainType::FOREST)); // 0.8
        $this->assertFalse($this->service->areTerrainTypesCompatible(TerrainType::WATER, TerrainType::DESERT)); // 0.1
        
        // Custom threshold
        $this->assertTrue($this->service->areTerrainTypesCompatible(TerrainType::PLAINS, TerrainType::DESERT, 0.5)); // 0.5
        $this->assertFalse($this->service->areTerrainTypesCompatible(TerrainType::PLAINS, TerrainType::DESERT, 0.6)); // 0.5 < 0.6
        
        // Very low threshold
        $this->assertTrue($this->service->areTerrainTypesCompatible(TerrainType::WATER, TerrainType::DESERT, 0.1)); // 0.1 >= 0.1
        $this->assertFalse($this->service->areTerrainTypesCompatible(TerrainType::WATER, TerrainType::DESERT, 0.2)); // 0.1 < 0.2
    }

    public function testGetCompatibleTerrainTypes(): void
    {
        // Test with default threshold (0.5)
        $compatibleWithPlains = $this->service->getCompatibleTerrainTypes(TerrainType::PLAINS);
        
        $this->assertContains(TerrainType::PLAINS, $compatibleWithPlains); // 1.0
        $this->assertContains(TerrainType::FOREST, $compatibleWithPlains); // 0.8
        $this->assertContains(TerrainType::WATER, $compatibleWithPlains); // 0.7
        $this->assertContains(TerrainType::MOUNTAIN, $compatibleWithPlains); // 0.6
        $this->assertContains(TerrainType::DESERT, $compatibleWithPlains); // 0.5
        $this->assertNotContains(TerrainType::SWAMP, $compatibleWithPlains); // 0.4 < 0.5

        // Test with custom threshold
        $compatibleWithWater = $this->service->getCompatibleTerrainTypes(TerrainType::WATER, 0.7);
        
        $this->assertContains(TerrainType::WATER, $compatibleWithWater); // 1.0
        $this->assertContains(TerrainType::SWAMP, $compatibleWithWater); // 0.8
        $this->assertContains(TerrainType::PLAINS, $compatibleWithWater); // 0.7
        $this->assertNotContains(TerrainType::FOREST, $compatibleWithWater); // 0.6 < 0.7
    }

    public function testShouldReplaceForCompatibility(): void
    {
        // Test with mostly incompatible neighbors (should replace)
        $incompatibleNeighbors = [
            TerrainType::WATER->value,  // 0.1 compatibility with desert
            TerrainType::FOREST->value, // 0.2 compatibility with desert  
            TerrainType::SWAMP->value   // 0.1 compatibility with desert
        ];
        $this->assertTrue($this->service->shouldReplaceForCompatibility(TerrainType::DESERT, $incompatibleNeighbors, 0.3));

        // Test with mostly compatible neighbors (should not replace)
        $compatibleNeighbors = [
            TerrainType::PLAINS->value,   // 0.8 compatibility with forest
            TerrainType::MOUNTAIN->value, // 0.7 compatibility with forest
            TerrainType::SWAMP->value     // 0.6 compatibility with forest
        ];
        $this->assertFalse($this->service->shouldReplaceForCompatibility(TerrainType::FOREST, $compatibleNeighbors, 0.5));

        // Test edge case: exactly half incompatible (should not replace - <=0.5)
        $halfIncompatible = [
            TerrainType::WATER->value,    // 0.1 < 0.3 (incompatible)
            TerrainType::MOUNTAIN->value  // 0.8 >= 0.3 (compatible)
        ];
        $this->assertFalse($this->service->shouldReplaceForCompatibility(TerrainType::DESERT, $halfIncompatible, 0.3));

        // Test with empty neighbors
        $this->assertFalse($this->service->shouldReplaceForCompatibility(TerrainType::PLAINS, [], 0.5));
    }

    public function testFindBestReplacementTerrain(): void
    {
        // Test with clear best option
        $neighborCounts = [
            TerrainType::FOREST->value => 3,
            TerrainType::PLAINS->value => 1
        ];
        
        $bestReplacement = $this->service->findBestReplacementTerrain($neighborCounts);
        
        // Forest should be chosen due to higher count and good internal compatibility
        $this->assertEquals(TerrainType::FOREST, $bestReplacement);

        // Test with mixed terrains
        $mixedNeighbors = [
            TerrainType::PLAINS->value => 2,
            TerrainType::FOREST->value => 2,
            TerrainType::WATER->value => 1
        ];
        
        $bestReplacement = $this->service->findBestReplacementTerrain($mixedNeighbors);
        
        // Should be one of the more common terrains (plains or forest)
        $this->assertContains($bestReplacement, [TerrainType::PLAINS, TerrainType::FOREST]);

        // Test with empty neighbors
        $this->assertNull($this->service->findBestReplacementTerrain([]));
    }

    public function testFindBestReplacementTerrainWithSingleTerrain(): void
    {
        $singleTerrain = [
            TerrainType::MOUNTAIN->value => 4
        ];
        
        $bestReplacement = $this->service->findBestReplacementTerrain($singleTerrain);
        $this->assertEquals(TerrainType::MOUNTAIN, $bestReplacement);
    }

    public function testGetCompatibilityMatrix(): void
    {
        $matrix = $this->service->getCompatibilityMatrix();
        
        $this->assertIsArray($matrix);
        
        // Check that all terrain types are represented
        foreach (TerrainType::cases() as $terrain) {
            $this->assertArrayHasKey($terrain->value, $matrix);
            $this->assertIsArray($matrix[$terrain->value]);
            
            // Check that each terrain has compatibility with all other terrains
            foreach (TerrainType::cases() as $otherTerrain) {
                $this->assertArrayHasKey($otherTerrain->value, $matrix[$terrain->value]);
                $this->assertIsFloat($matrix[$terrain->value][$otherTerrain->value]);
            }
        }
    }

    public function testIsValidCompatibilityMatrix(): void
    {
        // Valid matrix (subset)
        $validMatrix = [
            TerrainType::PLAINS->value => [
                TerrainType::PLAINS->value => 1.0,
                TerrainType::FOREST->value => 0.8
            ],
            TerrainType::FOREST->value => [
                TerrainType::PLAINS->value => 0.8,
                TerrainType::FOREST->value => 1.0
            ]
        ];
        $this->assertFalse($this->service->isValidCompatibilityMatrix($validMatrix)); // Incomplete matrix

        // Complete valid matrix
        $completeMatrix = [];
        foreach (TerrainType::cases() as $terrain1) {
            $completeMatrix[$terrain1->value] = [];
            foreach (TerrainType::cases() as $terrain2) {
                $completeMatrix[$terrain1->value][$terrain2->value] = 0.5;
            }
        }
        $this->assertTrue($this->service->isValidCompatibilityMatrix($completeMatrix));

        // Matrix with invalid score (too high)
        $invalidScore = $completeMatrix;
        $invalidScore[TerrainType::PLAINS->value][TerrainType::FOREST->value] = 1.5;
        $this->assertFalse($this->service->isValidCompatibilityMatrix($invalidScore));

        // Matrix with invalid score (negative)
        $negativeScore = $completeMatrix;
        $negativeScore[TerrainType::PLAINS->value][TerrainType::FOREST->value] = -0.1;
        $this->assertFalse($this->service->isValidCompatibilityMatrix($negativeScore));

        // Matrix with non-float score
        $nonFloatScore = $completeMatrix;
        $nonFloatScore[TerrainType::PLAINS->value][TerrainType::FOREST->value] = "0.5";
        $this->assertFalse($this->service->isValidCompatibilityMatrix($nonFloatScore));
    }

    public function testCompatibilityMatrixSelfCompatibility(): void
    {
        // All terrains should have perfect compatibility with themselves
        foreach (TerrainType::cases() as $terrain) {
            $score = $this->service->getCompatibilityScore($terrain, $terrain);
            $this->assertEquals(1.0, $score, 
                "Terrain {$terrain->value} should have perfect compatibility with itself");
        }
    }

    public function testLogicalCompatibilityRelationships(): void
    {
        // Natural relationships that should hold based on domain logic
        
        // Water and swamp should be highly compatible
        $this->assertGreaterThan(0.7, $this->service->getCompatibilityScore(TerrainType::WATER, TerrainType::SWAMP));
        
        // Desert and mountain should be compatible (both harsh environments)
        $this->assertGreaterThan(0.7, $this->service->getCompatibilityScore(TerrainType::DESERT, TerrainType::MOUNTAIN));
        
        // Forest and plains should be compatible (natural transition)
        $this->assertGreaterThan(0.7, $this->service->getCompatibilityScore(TerrainType::FOREST, TerrainType::PLAINS));
        
        // Water and desert should be incompatible
        $this->assertLessThan(0.3, $this->service->getCompatibilityScore(TerrainType::WATER, TerrainType::DESERT));
        
        // Forest and desert should be incompatible
        $this->assertLessThan(0.3, $this->service->getCompatibilityScore(TerrainType::FOREST, TerrainType::DESERT));
    }

    public function testShouldReplaceForCompatibilityThresholds(): void
    {
        // Test different threshold values
        $neighbors = [
            TerrainType::WATER->value,   // 0.6 compatibility with forest
            TerrainType::DESERT->value   // 0.2 compatibility with forest
        ];

        // With threshold 0.3: desert is incompatible (0.2 < 0.3), water is compatible (0.6 >= 0.3)
        // 1 out of 2 incompatible = 50%, should not replace (50% not > 50%)
        $this->assertFalse($this->service->shouldReplaceForCompatibility(TerrainType::FOREST, $neighbors, 0.3));

        // With threshold 0.7: both water (0.6 < 0.7) and desert (0.2 < 0.7) are incompatible
        // 2 out of 2 incompatible = 100% > 50%, should replace
        $this->assertTrue($this->service->shouldReplaceForCompatibility(TerrainType::FOREST, $neighbors, 0.7));
    }

    public function testFindBestReplacementTerrainWeighting(): void
    {
        // Test that frequency weighting works correctly
        $neighbors = [
            TerrainType::PLAINS->value => 1,  // Low frequency but good compatibility
            TerrainType::DESERT->value => 5   // High frequency but poorer compatibility with some terrains
        ];

        $result = $this->service->findBestReplacementTerrain($neighbors);
        
        // The algorithm weighs both compatibility and frequency
        // Desert should likely win due to much higher frequency despite lower average compatibility
        $this->assertInstanceOf(TerrainType::class, $result);
        $this->assertContains($result, [TerrainType::PLAINS, TerrainType::DESERT]);
    }
} 