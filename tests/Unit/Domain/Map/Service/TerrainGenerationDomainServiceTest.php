<?php

namespace Tests\Unit\Domain\Map\Service;

use App\Domain\Map\Service\TerrainGenerationDomainService;
use App\Domain\Map\Enum\TerrainType;
use PHPUnit\Framework\TestCase;

class TerrainGenerationDomainServiceTest extends TestCase
{
    private TerrainGenerationDomainService $service;

    protected function setUp(): void
    {
        $this->service = new TerrainGenerationDomainService();
    }

    public function testGetTerrainWeights(): void
    {
        $weights = $this->service->getTerrainWeights();

        $this->assertIsArray($weights);
        $this->assertArrayHasKey(TerrainType::PLAINS->value, $weights);
        $this->assertArrayHasKey(TerrainType::FOREST->value, $weights);
        $this->assertArrayHasKey(TerrainType::MOUNTAIN->value, $weights);
        $this->assertArrayHasKey(TerrainType::WATER->value, $weights);
        $this->assertArrayHasKey(TerrainType::DESERT->value, $weights);
        $this->assertArrayHasKey(TerrainType::SWAMP->value, $weights);

        // Check that all weights are positive integers
        foreach ($weights as $terrain => $weight) {
            $this->assertIsInt($weight);
            $this->assertGreaterThan(0, $weight);
        }

        // Verify expected weight distribution (Plains should be most common)
        $this->assertGreaterThan($weights[TerrainType::FOREST->value], $weights[TerrainType::PLAINS->value]);
        $this->assertGreaterThan($weights[TerrainType::SWAMP->value], $weights[TerrainType::PLAINS->value]);
    }

    public function testGetWeightedRandomTerrain(): void
    {
        // Test that it returns valid terrain types
        for ($i = 0; $i < 100; $i++) {
            $terrain = $this->service->getWeightedRandomTerrain();
            $this->assertInstanceOf(TerrainType::class, $terrain);
        }
    }

    public function testWeightedRandomTerrainDistribution(): void
    {
        // Test distribution over many calls to ensure weighted randomness works
        $results = [];
        $iterations = 10000;

        for ($i = 0; $i < $iterations; $i++) {
            $terrain = $this->service->getWeightedRandomTerrain();
            $results[$terrain->value] = ($results[$terrain->value] ?? 0) + 1;
        }

        // Convert to percentages
        $percentages = [];
        foreach ($results as $terrain => $count) {
            $percentages[$terrain] = ($count / $iterations) * 100;
        }

        // Plains should be most common (around 35%)
        $this->assertGreaterThan(30, $percentages[TerrainType::PLAINS->value]);
        $this->assertLessThan(40, $percentages[TerrainType::PLAINS->value]);

        // Swamp should be least common (around 5%)
        $this->assertGreaterThan(2, $percentages[TerrainType::SWAMP->value]);
        $this->assertLessThan(8, $percentages[TerrainType::SWAMP->value]);

        // Forest should be second most common (around 25%)
        $this->assertGreaterThan(20, $percentages[TerrainType::FOREST->value]);
        $this->assertLessThan(30, $percentages[TerrainType::FOREST->value]);
    }

    public function testCreateTerrainTile(): void
    {
        $row = 5;
        $col = 10;
        $terrain = TerrainType::FOREST;

        $tile = $this->service->createTerrainTile($terrain, $row, $col);

        $this->assertIsArray($tile);
        $this->assertArrayHasKey('type', $tile);
        $this->assertArrayHasKey('name', $tile);
        $this->assertArrayHasKey('properties', $tile);
        $this->assertArrayHasKey('coordinates', $tile);

        $this->assertEquals($terrain->value, $tile['type']);
        $this->assertEquals('Forest', $tile['name']);
        $this->assertEquals($row, $tile['coordinates']['row']);
        $this->assertEquals($col, $tile['coordinates']['col']);

        // Verify properties structure
        $properties = $tile['properties'];
        $this->assertArrayHasKey('name', $properties);
        $this->assertArrayHasKey('color', $properties);
        $this->assertArrayHasKey('movementCost', $properties);
        $this->assertArrayHasKey('defense', $properties);
        $this->assertArrayHasKey('resources', $properties);
    }

    public function testCreateTerrainTileForAllTerrainTypes(): void
    {
        foreach (TerrainType::cases() as $terrain) {
            $tile = $this->service->createTerrainTile($terrain, 0, 0);

            $this->assertEquals($terrain->value, $tile['type']);
            $this->assertIsString($tile['name']);
            $this->assertIsArray($tile['properties']);
            $this->assertIsArray($tile['coordinates']);

            // Verify terrain-specific properties (using legacy properties for backward compatibility)
            $expectedProperties = $terrain->getLegacyProperties();
            $this->assertEquals($expectedProperties, $tile['properties']);
        }
    }

    public function testAreValidTerrainWeights(): void
    {
        // Valid weights
        $validWeights = [
            TerrainType::PLAINS->value => 30,
            TerrainType::FOREST->value => 20,
            TerrainType::WATER->value => 15
        ];
        $this->assertTrue($this->service->areValidTerrainWeights($validWeights));

        // Invalid terrain type
        $invalidTerrain = [
            'invalid_terrain' => 10,
            TerrainType::PLAINS->value => 20
        ];
        $this->assertFalse($this->service->areValidTerrainWeights($invalidTerrain));

        // Negative weight
        $negativeWeight = [
            TerrainType::PLAINS->value => -5,
            TerrainType::FOREST->value => 20
        ];
        $this->assertFalse($this->service->areValidTerrainWeights($negativeWeight));

        // Non-integer weight
        $floatWeight = [
            TerrainType::PLAINS->value => 15.5,
            TerrainType::FOREST->value => 20
        ];
        $this->assertFalse($this->service->areValidTerrainWeights($floatWeight));

        // Zero weight (should be valid)
        $zeroWeight = [
            TerrainType::PLAINS->value => 0,
            TerrainType::FOREST->value => 20
        ];
        $this->assertTrue($this->service->areValidTerrainWeights($zeroWeight));
    }

    public function testGetIdealTerrainDistribution(): void
    {
        $distribution = $this->service->getIdealTerrainDistribution();

        $this->assertIsArray($distribution);

        // Check that all terrain types are represented
        foreach (TerrainType::cases() as $terrain) {
            $this->assertArrayHasKey($terrain->value, $distribution);
            $this->assertIsFloat($distribution[$terrain->value]);
            $this->assertGreaterThanOrEqual(0, $distribution[$terrain->value]);
        }

        // Check that percentages sum to 100%
        $totalPercentage = array_sum($distribution);
        $this->assertEqualsWithDelta(100.0, $totalPercentage, 0.01);

        // Check expected distribution
        $this->assertGreaterThan($distribution[TerrainType::FOREST->value], $distribution[TerrainType::PLAINS->value]);
        $this->assertGreaterThan($distribution[TerrainType::SWAMP->value], $distribution[TerrainType::PLAINS->value]);
    }

    public function testCreateTerrainTileWithDifferentCoordinates(): void
    {
        $testCases = [
            [0, 0],
            [50, 75],
            [99, 99],
            [-1, -1], // Coordinates can be negative in some cases
        ];

        foreach ($testCases as [$row, $col]) {
            $tile = $this->service->createTerrainTile(TerrainType::PLAINS, $row, $col);

            $this->assertEquals($row, $tile['coordinates']['row']);
            $this->assertEquals($col, $tile['coordinates']['col']);
        }
    }

    public function testTerrainTilePropertiesConsistency(): void
    {
        // Verify that tile properties match terrain enum properties
        foreach (TerrainType::cases() as $terrain) {
            $tile = $this->service->createTerrainTile($terrain, 0, 0);
            $expectedProperties = $terrain->getLegacyProperties();

            $this->assertEquals($expectedProperties, $tile['properties']);
            $this->assertEquals($expectedProperties['name'], $tile['name']);
        }
    }
} 