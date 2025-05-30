<?php

namespace Tests\Unit\Domain\Map\Service;

use App\Domain\Map\Service\TerrainClusteringDomainService;
use App\Domain\Map\Enum\TerrainType;
use PHPUnit\Framework\TestCase;

class TerrainClusteringDomainServiceTest extends TestCase
{
    private TerrainClusteringDomainService $service;

    protected function setUp(): void
    {
        $this->service = new TerrainClusteringDomainService();
    }

    public function testShouldTerrainCluster(): void
    {
        // All terrain types should have clustering behavior
        foreach (TerrainType::cases() as $terrain) {
            $this->assertTrue($this->service->shouldTerrainCluster($terrain));
        }
    }

    public function testGetClusteringProbability(): void
    {
        // Test known probabilities
        $this->assertEquals(0.7, $this->service->getClusteringProbability(TerrainType::WATER));
        $this->assertEquals(0.6, $this->service->getClusteringProbability(TerrainType::FOREST));
        $this->assertEquals(0.6, $this->service->getClusteringProbability(TerrainType::DESERT));
        $this->assertEquals(0.5, $this->service->getClusteringProbability(TerrainType::MOUNTAIN));
        $this->assertEquals(0.4, $this->service->getClusteringProbability(TerrainType::SWAMP));
        $this->assertEquals(0.3, $this->service->getClusteringProbability(TerrainType::PLAINS));

        // All probabilities should be between 0 and 1
        foreach (TerrainType::cases() as $terrain) {
            $probability = $this->service->getClusteringProbability($terrain);
            $this->assertGreaterThanOrEqual(0.0, $probability);
            $this->assertLessThanOrEqual(1.0, $probability);
        }
    }

    public function testGetClusteringConfiguration(): void
    {
        $config = $this->service->getClusteringConfiguration();

        $this->assertIsArray($config);

        // Check that all terrain types are in configuration
        foreach (TerrainType::cases() as $terrain) {
            $this->assertArrayHasKey($terrain->value, $config);
            $this->assertIsFloat($config[$terrain->value]);
            $this->assertGreaterThanOrEqual(0.0, $config[$terrain->value]);
            $this->assertLessThanOrEqual(1.0, $config[$terrain->value]);
        }

        // Verify hierarchy: water > forest/desert > mountain > swamp > plains
        $this->assertGreaterThan($config[TerrainType::FOREST->value], $config[TerrainType::WATER->value]);
        $this->assertGreaterThan($config[TerrainType::MOUNTAIN->value], $config[TerrainType::FOREST->value]);
        $this->assertGreaterThan($config[TerrainType::SWAMP->value], $config[TerrainType::MOUNTAIN->value]);
        $this->assertGreaterThan($config[TerrainType::PLAINS->value], $config[TerrainType::SWAMP->value]);
    }

    public function testShouldSpreadToNeighborWithHighClusterCount(): void
    {
        // Should not spread if already well-clustered (>=2 same neighbors)
        $result = $this->service->shouldSpreadToNeighbor(TerrainType::WATER, 2, 6);
        $this->assertFalse($result);

        $result = $this->service->shouldSpreadToNeighbor(TerrainType::FOREST, 3, 6);
        $this->assertFalse($result);
    }

    public function testShouldSpreadToNeighborRandomness(): void
    {
        // Test with low cluster count - should be based on random chance
        // We can't test exact randomness, but we can test that it sometimes returns true/false

        $trueResults = 0;
        $falseResults = 0;
        $iterations = 1000;

        // Use a terrain with medium clustering probability
        for ($i = 0; $i < $iterations; $i++) {
            if ($this->service->shouldSpreadToNeighbor(TerrainType::FOREST, 0, 6)) {
                $trueResults++;
            } else {
                $falseResults++;
            }
        }

        // Should have both true and false results due to randomness
        $this->assertGreaterThan(0, $trueResults);
        $this->assertGreaterThan(0, $falseResults);

        // For FOREST (0.6 probability), expect roughly 60% true results
        $truePercentage = ($trueResults / $iterations) * 100;
        $this->assertGreaterThan(50, $truePercentage);
        $this->assertLessThan(70, $truePercentage);
    }

    public function testCountSameTerrainNeighbors(): void
    {
        $neighbors = [
            ['type' => TerrainType::FOREST->value],
            ['type' => TerrainType::PLAINS->value],
            ['type' => TerrainType::FOREST->value],
            ['type' => TerrainType::WATER->value],
            ['type' => TerrainType::FOREST->value],
            ['type' => TerrainType::MOUNTAIN->value]
        ];

        $count = $this->service->countSameTerrainNeighbors($neighbors, TerrainType::FOREST);
        $this->assertEquals(3, $count);

        $count = $this->service->countSameTerrainNeighbors($neighbors, TerrainType::PLAINS);
        $this->assertEquals(1, $count);

        $count = $this->service->countSameTerrainNeighbors($neighbors, TerrainType::DESERT);
        $this->assertEquals(0, $count);
    }

    public function testCountSameTerrainNeighborsWithEmptyArray(): void
    {
        $neighbors = [];
        $count = $this->service->countSameTerrainNeighbors($neighbors, TerrainType::FOREST);
        $this->assertEquals(0, $count);
    }

    public function testSelectNeighborToConvert(): void
    {
        $neighbors = [
            ['type' => TerrainType::FOREST->value, 'coordinates' => ['row' => 1, 'col' => 1]],
            ['type' => TerrainType::PLAINS->value, 'coordinates' => ['row' => 1, 'col' => 2]],
            ['type' => TerrainType::WATER->value, 'coordinates' => ['row' => 2, 'col' => 1]]
        ];

        // Test multiple times to account for randomness
        $results = [];
        for ($i = 0; $i < 100; $i++) {
            $result = $this->service->selectNeighborToConvert($neighbors, TerrainType::FOREST);
            $results[] = $result;
        }

        // Should sometimes return null (70% chance) and sometimes return a neighbor (30% chance)
        $nullResults = count(array_filter($results, fn($r) => $r === null));
        $nonNullResults = count(array_filter($results, fn($r) => $r !== null));

        $this->assertGreaterThan(0, $nullResults);
        $this->assertGreaterThan(0, $nonNullResults);

        // When it returns a neighbor, it should be one of the different-type neighbors
        foreach ($results as $result) {
            if ($result !== null) {
                $this->assertIsArray($result);
                $this->assertArrayHasKey('type', $result);
                $this->assertNotEquals(TerrainType::FOREST->value, $result['type']);
                $this->assertContains($result['type'], [TerrainType::PLAINS->value, TerrainType::WATER->value]);
            }
        }
    }

    public function testSelectNeighborToConvertWithNoValidNeighbors(): void
    {
        // All neighbors are same type as current terrain
        $neighbors = [
            ['type' => TerrainType::FOREST->value],
            ['type' => TerrainType::FOREST->value],
            ['type' => TerrainType::FOREST->value]
        ];

        $result = $this->service->selectNeighborToConvert($neighbors, TerrainType::FOREST);
        $this->assertNull($result);
    }

    public function testSelectNeighborToConvertWithEmptyNeighbors(): void
    {
        $neighbors = [];
        $result = $this->service->selectNeighborToConvert($neighbors, TerrainType::FOREST);
        $this->assertNull($result);
    }

    public function testIsValidClusteringConfiguration(): void
    {
        // Valid configuration
        $validConfig = [
            TerrainType::PLAINS->value => 0.3,
            TerrainType::FOREST->value => 0.6,
            TerrainType::WATER->value => 0.7
        ];
        $this->assertTrue($this->service->isValidClusteringConfiguration($validConfig));

        // Invalid terrain type
        $invalidTerrain = [
            'invalid_terrain' => 0.5,
            TerrainType::PLAINS->value => 0.3
        ];
        $this->assertFalse($this->service->isValidClusteringConfiguration($invalidTerrain));

        // Probability too high
        $tooHigh = [
            TerrainType::PLAINS->value => 1.5,
            TerrainType::FOREST->value => 0.6
        ];
        $this->assertFalse($this->service->isValidClusteringConfiguration($tooHigh));

        // Probability too low
        $tooLow = [
            TerrainType::PLAINS->value => -0.1,
            TerrainType::FOREST->value => 0.6
        ];
        $this->assertFalse($this->service->isValidClusteringConfiguration($tooLow));

        // Non-float probability
        $nonFloat = [
            TerrainType::PLAINS->value => "0.5",
            TerrainType::FOREST->value => 0.6
        ];
        $this->assertFalse($this->service->isValidClusteringConfiguration($nonFloat));

        // Edge cases: exactly 0.0 and 1.0 should be valid
        $edgeCases = [
            TerrainType::PLAINS->value => 0.0,
            TerrainType::FOREST->value => 1.0
        ];
        $this->assertTrue($this->service->isValidClusteringConfiguration($edgeCases));
    }

    public function testClusteringHierarchy(): void
    {
        // Verify that water has highest clustering tendency
        $waterProb = $this->service->getClusteringProbability(TerrainType::WATER);
        
        foreach (TerrainType::cases() as $terrain) {
            if ($terrain !== TerrainType::WATER) {
                $prob = $this->service->getClusteringProbability($terrain);
                $this->assertGreaterThanOrEqual($prob, $waterProb, 
                    "Water should have highest or equal clustering probability compared to {$terrain->value}");
            }
        }

        // Verify that plains has lowest clustering tendency
        $plainsProb = $this->service->getClusteringProbability(TerrainType::PLAINS);
        
        foreach (TerrainType::cases() as $terrain) {
            if ($terrain !== TerrainType::PLAINS) {
                $prob = $this->service->getClusteringProbability($terrain);
                $this->assertLessThanOrEqual($prob, $plainsProb, 
                    "Plains should have lowest or equal clustering probability compared to {$terrain->value}");
            }
        }
    }

    public function testSelectNeighborToConvertDistribution(): void
    {
        // Test that when conversion happens, all different neighbors have a chance to be selected
        $neighbors = [
            ['type' => TerrainType::PLAINS->value, 'id' => 1],
            ['type' => TerrainType::WATER->value, 'id' => 2],
            ['type' => TerrainType::MOUNTAIN->value, 'id' => 3],
            ['type' => TerrainType::FOREST->value, 'id' => 4] // Same as current, should not be selected
        ];

        $selectedIds = [];
        for ($i = 0; $i < 1000; $i++) {
            $result = $this->service->selectNeighborToConvert($neighbors, TerrainType::FOREST);
            if ($result !== null) {
                $selectedIds[] = $result['id'];
            }
        }

        // Should have selected each different neighbor type at least once
        $uniqueIds = array_unique($selectedIds);
        $this->assertContains(1, $uniqueIds); // PLAINS
        $this->assertContains(2, $uniqueIds); // WATER
        $this->assertContains(3, $uniqueIds); // MOUNTAIN
        $this->assertNotContains(4, $uniqueIds); // FOREST (same type) should never be selected
    }
} 