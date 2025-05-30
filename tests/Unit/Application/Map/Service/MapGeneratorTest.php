<?php

namespace Tests\Unit\Application\Map\Service;

use App\Application\Map\Service\MapGenerator;
use App\Application\Map\Service\BaseTerrainGenerationService;
use App\Application\Map\Service\TerrainClusteringService;
use App\Application\Map\Service\TerrainSmoothingService;
use App\Application\Map\Service\MapValidationService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for MapGenerator
 */
class MapGeneratorTest extends TestCase
{
    private MapGenerator $mapGenerator;
    private BaseTerrainGenerationService|MockObject $baseTerrainService;
    private TerrainClusteringService|MockObject $clusteringService;
    private TerrainSmoothingService|MockObject $smoothingService;
    private MapValidationService|MockObject $validationService;

    protected function setUp(): void
    {
        $this->baseTerrainService = $this->createMock(BaseTerrainGenerationService::class);
        $this->clusteringService = $this->createMock(TerrainClusteringService::class);
        $this->smoothingService = $this->createMock(TerrainSmoothingService::class);
        $this->validationService = $this->createMock(MapValidationService::class);

        $this->mapGenerator = new MapGenerator(
            $this->baseTerrainService,
            $this->clusteringService,
            $this->smoothingService,
            $this->validationService
        );
    }

    public function testGenerateMapExecutesAllPhases(): void
    {
        $rows = 10;
        $cols = 10;

        $baseMap = [
            [['type' => 'plains', 'name' => 'Plains']],
            [['type' => 'forest', 'name' => 'Forest']]
        ];

        $clusteredMap = [
            [['type' => 'plains', 'name' => 'Plains']],
            [['type' => 'plains', 'name' => 'Plains']]
        ];

        $smoothedMap = [
            [['type' => 'plains', 'name' => 'Plains']],
            [['type' => 'plains', 'name' => 'Plains']]
        ];

        $polishedMap = [
            [['type' => 'plains', 'name' => 'Plains']],
            [['type' => 'plains', 'name' => 'Plains']]
        ];

        $finalMap = [
            [['type' => 'plains', 'name' => 'Plains']],
            [['type' => 'plains', 'name' => 'Plains']]
        ];

        // Mock each phase of generation
        $this->baseTerrainService->expects($this->once())
            ->method('generateBaseMap')
            ->with($rows, $cols)
            ->willReturn($baseMap);

        $this->clusteringService->expects($this->once())
            ->method('applyClustering')
            ->with($baseMap, $rows, $cols, 2)
            ->willReturn($clusteredMap);

        $this->smoothingService->expects($this->once())
            ->method('applyCompatibilitySmoothing')
            ->with($clusteredMap, $rows, $cols)
            ->willReturn($smoothedMap);

        $this->validationService->expects($this->once())
            ->method('polishMap')
            ->with($smoothedMap, $rows, $cols)
            ->willReturn($polishedMap);

        $this->validationService->expects($this->once())
            ->method('ensurePassableTerrain')
            ->with($polishedMap, $rows, $cols)
            ->willReturn($finalMap);

        $result = $this->mapGenerator->generateMap($rows, $cols);

        $this->assertEquals($finalMap, $result);
    }

    public function testGenerateBalancedMapWithValidation(): void
    {
        $rows = 20;
        $cols = 30;
        $options = [
            'clustering_iterations' => 3,
            'fix_small_clusters' => true,
            'min_cluster_size' => 4
        ];

        $baseMap = [
            [['type' => 'plains', 'name' => 'Plains']]
        ];

        $validation = [
            'isValid' => true,
            'issues' => [],
            'suggestions' => []
        ];

        $statistics = [
            'plains' => 60.0,
            'forest' => 25.0,
            'mountain' => 15.0
        ];

        // Setup method call chain
        $this->baseTerrainService->expects($this->once())
            ->method('generateBaseMap')
            ->willReturn($baseMap);

        $this->clusteringService->expects($this->once())
            ->method('applyClustering')
            ->with($baseMap, $rows, $cols, 3)
            ->willReturn($baseMap);

        $this->smoothingService->expects($this->once())
            ->method('applyCompatibilitySmoothing')
            ->willReturn($baseMap);

        $this->validationService->expects($this->once())
            ->method('fixSmallClusters')
            ->with($baseMap, $rows, $cols, 4)
            ->willReturn($baseMap);

        $this->validationService->expects($this->once())
            ->method('polishMap')
            ->willReturn($baseMap);

        $this->validationService->expects($this->once())
            ->method('ensurePassableTerrain')
            ->willReturn($baseMap);

        $this->validationService->expects($this->once())
            ->method('validateMapBalance')
            ->with($baseMap, $rows, $cols)
            ->willReturn($validation);

        $this->validationService->expects($this->once())
            ->method('calculateTerrainStatistics')
            ->with($baseMap, $rows, $cols)
            ->willReturn($statistics);

        $result = $this->mapGenerator->generateBalancedMap($rows, $cols, $options);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('map', $result);
        $this->assertArrayHasKey('validation', $result);
        $this->assertArrayHasKey('statistics', $result);
        $this->assertEquals($baseMap, $result['map']);
        $this->assertEquals($validation, $result['validation']);
        $this->assertEquals($statistics, $result['statistics']);
    }

    public function testGenerateCompetitiveMapIncludesAnalysis(): void
    {
        $rows = 20;
        $cols = 30;
        $expectedPlayers = 4;

        $baseMap = [
            [['type' => 'plains', 'name' => 'Plains']]
        ];

        $validation = ['isValid' => true];
        $statistics = ['plains' => 60.0, 'forest' => 25.0, 'mountain' => 15.0];

        // Setup balanced map generation chain
        $this->setupBalancedMapGeneration($baseMap, $validation, $statistics);

        $result = $this->mapGenerator->generateCompetitiveMap($rows, $cols, $expectedPlayers);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('map', $result);
        $this->assertArrayHasKey('validation', $result);
        $this->assertArrayHasKey('statistics', $result);
        $this->assertArrayHasKey('competitive_analysis', $result);
        
        $competitiveAnalysis = $result['competitive_analysis'];
        $this->assertArrayHasKey('player_balance_score', $competitiveAnalysis);
        $this->assertArrayHasKey('strategic_depth_score', $competitiveAnalysis);
        $this->assertArrayHasKey('terrain_variety_score', $competitiveAnalysis);
        $this->assertArrayHasKey('movement_freedom_score', $competitiveAnalysis);
        $this->assertArrayHasKey('overall_competitive_score', $competitiveAnalysis);
    }

    public function testGenerateThemedMapWithTerrainEmphasis(): void
    {
        $rows = 15;
        $cols = 20;
        $terrainEmphasis = [
            'forest' => 40,
            'mountain' => 30
        ];

        $baseMap = [
            [['type' => 'forest', 'name' => 'Forest']],
            [['type' => 'mountain', 'name' => 'Mountain']]
        ];

        $statistics = [
            'forest' => 45.0,
            'mountain' => 35.0,
            'plains' => 20.0
        ];

        // Setup standard map generation
        $this->setupStandardMapGeneration($baseMap);

        $this->validationService->expects($this->once())
            ->method('calculateTerrainStatistics')
            ->willReturn($statistics);

        $result = $this->mapGenerator->generateThemedMap($rows, $cols, $terrainEmphasis);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('map', $result);
        $this->assertArrayHasKey('statistics', $result);
        $this->assertArrayHasKey('theme_analysis', $result);
        
        $themeAnalysis = $result['theme_analysis'];
        $this->assertArrayHasKey('forest', $themeAnalysis);
        $this->assertArrayHasKey('mountain', $themeAnalysis);
    }

    public function testGetTerrainStatistics(): void
    {
        $map = [
            [['type' => 'plains', 'name' => 'Plains']]
        ];
        $expectedStats = ['plains' => 100.0];

        $this->validationService->expects($this->once())
            ->method('calculateTerrainStatistics')
            ->with($map, 10, 10)
            ->willReturn($expectedStats);

        $result = $this->mapGenerator->getTerrainStatistics($map, 10, 10);

        $this->assertEquals($expectedStats, $result);
    }

    public function testValidateMap(): void
    {
        $map = [
            [['type' => 'plains', 'name' => 'Plains']]
        ];
        $expectedValidation = ['isValid' => true];

        $this->validationService->expects($this->once())
            ->method('validateMapBalance')
            ->with($map, 10, 10)
            ->willReturn($expectedValidation);

        $result = $this->mapGenerator->validateMap($map, 10, 10);

        $this->assertEquals($expectedValidation, $result);
    }

    public function testAnalyzeStrategicElements(): void
    {
        $map = [
            [['type' => 'plains', 'name' => 'Plains']]
        ];

        $statistics = [
            'plains' => 50.0,
            'forest' => 25.0,
            'mountain' => 15.0,
            'water' => 10.0
        ];

        $this->validationService->expects($this->once())
            ->method('calculateTerrainStatistics')
            ->with($map, 10, 10)
            ->willReturn($statistics);

        $result = $this->mapGenerator->analyzeStrategicElements($map, 10, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('defensive_terrain_percentage', $result);
        $this->assertArrayHasKey('movement_restriction_percentage', $result);
        $this->assertArrayHasKey('resource_terrain_percentage', $result);
        $this->assertArrayHasKey('mobility_terrain_percentage', $result);
        $this->assertArrayHasKey('strategic_balance_score', $result);
        
        $this->assertEquals(50.0, $result['mobility_terrain_percentage']);
    }

    public function testGetMapImprovementRecommendations(): void
    {
        $map = [
            [['type' => 'water', 'name' => 'Water']] // All water - poor balance
        ];

        $validation = [
            'isValid' => false,
            'suggestions' => ['Reduce water percentage']
        ];

        $statistics = ['water' => 100.0];

        $this->validationService->expects($this->once())
            ->method('validateMapBalance')
            ->willReturn($validation);

        $this->validationService->expects($this->once())
            ->method('calculateTerrainStatistics')
            ->willReturn($statistics);

        $result = $this->mapGenerator->getMapImprovementRecommendations($map, 10, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('balance_issues', $result);
        $this->assertArrayHasKey('strategic_improvements', $result);
        $this->assertEquals(['Reduce water percentage'], $result['balance_issues']);
    }

    public function testGetClusteringConfiguration(): void
    {
        $expectedConfig = ['forest' => 0.8, 'mountain' => 0.7];

        $this->clusteringService->expects($this->once())
            ->method('getClusteringConfiguration')
            ->willReturn($expectedConfig);

        $result = $this->mapGenerator->getClusteringConfiguration();

        $this->assertEquals($expectedConfig, $result);
    }

    public function testGetCompatibilityMatrix(): void
    {
        $expectedMatrix = ['plains' => ['forest' => 0.8]];

        $this->smoothingService->expects($this->once())
            ->method('getCompatibilityMatrix')
            ->willReturn($expectedMatrix);

        $result = $this->mapGenerator->getCompatibilityMatrix();

        $this->assertEquals($expectedMatrix, $result);
    }

    public function testGetTerrainWeights(): void
    {
        $expectedWeights = ['plains' => 35, 'forest' => 25];

        $this->baseTerrainService->expects($this->once())
            ->method('getTerrainWeights')
            ->willReturn($expectedWeights);

        $result = $this->mapGenerator->getTerrainWeights();

        $this->assertEquals($expectedWeights, $result);
    }

    // Helper methods for reducing test duplication

    private function setupStandardMapGeneration(array $map): void
    {
        $this->baseTerrainService->method('generateBaseMap')->willReturn($map);
        $this->clusteringService->method('applyClustering')->willReturn($map);
        $this->smoothingService->method('applyCompatibilitySmoothing')->willReturn($map);
        $this->validationService->method('polishMap')->willReturn($map);
        $this->validationService->method('ensurePassableTerrain')->willReturn($map);
    }

    private function setupBalancedMapGeneration(array $map, array $validation, array $statistics): void
    {
        $this->setupStandardMapGeneration($map);
        $this->validationService->method('fixSmallClusters')->willReturn($map);
        $this->validationService->method('validateMapBalance')->willReturn($validation);
        $this->validationService->method('calculateTerrainStatistics')->willReturn($statistics);
    }
} 