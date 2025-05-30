<?php

namespace App\Application\Map\Service;

use App\Domain\Map\Enum\TerrainType;

/**
 * MapGenerator service serves as a facade for map generation operations
 *
 * Orchestrates between specialized map generation services to provide a unified interface
 * for map creation. Delegates responsibilities to focused services following SOLID
 * principles while providing domain-focused high-level operations.
 */
class MapGenerator
{
    /** @var int Default clustering iterations for map generation */
    private const int DEFAULT_CLUSTERING_ITERATIONS = 2;

    /** @var int Enhanced clustering iterations for competitive maps */
    private const int COMPETITIVE_CLUSTERING_ITERATIONS = 3;

    /** @var int Default minimum cluster size for validation */
    private const int DEFAULT_MIN_CLUSTER_SIZE = 3;

    /** @var int Minimum cluster size for competitive maps */
    private const int COMPETITIVE_MIN_CLUSTER_SIZE = 4;

    /** @var float Strategic balance score threshold */
    private const float STRATEGIC_BALANCE_THRESHOLD = 0.7;

    /** @var int Maximum terrain dominance percentage */
    private const int MAX_TERRAIN_DOMINANCE = 60;

    /** @var int Target mobility percentage for competitive maps */
    private const int TARGET_MOBILITY_PERCENTAGE = 50;

    /** @var int Maximum expected players for balancing calculations */
    private const int MAX_EXPECTED_PLAYERS = 4;

    /** @var float Dominance penalty multiplier */
    private const float DOMINANCE_PENALTY = 0.5;

    /** @var int Maximum possible terrain distribution deviation */
    private const int MAX_DEVIATION = 200;

    public function __construct(
        private readonly BaseTerrainGenerationService $baseTerrainService,
        private readonly TerrainClusteringService     $clusteringService,
        private readonly TerrainSmoothingService      $smoothingService,
        private readonly MapValidationService         $validationService,
    ) {
    }

    /**
     * Generates a realistic map with weighted terrain distribution and clustering
     *
     * Creates a natural-looking hexagonal map through multiple generation phases:
     * 1. Initial weighted random placement
     * 2. Clustering enhancement based on terrain preferences
     * 3. Compatibility smoothing for realistic transitions
     * 4. Final polish pass for edge cases
     *
     * @param int $rows Number of rows in the map grid
     * @param int $cols Number of columns in the map grid
     *
     * @return array 2D array of terrain tiles with realistic distribution
     */
    public function generateMap(int $rows, int $cols): array
    {
        // Phase 1: Initialize map with weighted random terrain
        $map = $this->baseTerrainService->generateBaseMap($rows, $cols);

        // Phase 2: Apply clustering to create terrain formations
        $map = $this->clusteringService->applyClustering($map, $rows, $cols, self::DEFAULT_CLUSTERING_ITERATIONS);

        // Phase 3: Apply compatibility smoothing for natural transitions
        $map = $this->smoothingService->applyCompatibilitySmoothing($map, $rows, $cols);

        // Phase 4: Final polish pass to fix isolated tiles
        $map = $this->validationService->polishMap($map, $rows, $cols);

        // Phase 5: Ensure map playability
        $map = $this->validationService->ensurePassableTerrain($map, $rows, $cols);

        return $map;
    }

    /**
     * Generates a map with enhanced validation and balancing
     *
     * @param int $rows Number of rows in the map grid
     * @param int $cols Number of columns in the map grid
     * @param array $options Generation options (clustering iterations, validation level, etc.)
     * @return array Complete map generation result with statistics
     */
    public function generateBalancedMap(int $rows, int $cols, array $options = []): array
    {
        $clusteringIterations = $options['clustering_iterations'] ?? self::DEFAULT_CLUSTERING_ITERATIONS;
        $enableSmallClusterFix = $options['fix_small_clusters'] ?? true;
        $minClusterSize = $options['min_cluster_size'] ?? self::DEFAULT_MIN_CLUSTER_SIZE;

        // Generate base map
        $map = $this->baseTerrainService->generateBaseMap($rows, $cols);

        // Apply clustering with custom iterations
        $map = $this->clusteringService->applyClustering($map, $rows, $cols, $clusteringIterations);

        // Apply smoothing
        $map = $this->smoothingService->applyCompatibilitySmoothing($map, $rows, $cols);

        // Fix small clusters if enabled
        if ($enableSmallClusterFix) {
            $map = $this->validationService->fixSmallClusters($map, $rows, $cols, $minClusterSize);
        }

        // Final polish
        $map = $this->validationService->polishMap($map, $rows, $cols);

        // Ensure playability
        $map = $this->validationService->ensurePassableTerrain($map, $rows, $cols);

        // Validate map balance
        $validation = $this->validationService->validateMapBalance($map, $rows, $cols);

        return [
            'map' => $map,
            'validation' => $validation,
            'statistics' => $this->validationService->calculateTerrainStatistics($map, $rows, $cols)
        ];
    }

    /**
     * Generates a map optimized for competitive gameplay
     *
     * Creates a balanced map with specific requirements for player vs player scenarios:
     * - Ensures adequate movement paths
     * - Balances defensive and offensive terrain
     * - Provides strategic resource distribution
     *
     * @param int $rows Number of rows in the map grid
     * @param int $cols Number of columns in the map grid
     * @param int $expectedPlayers Number of expected players
     * @return array Competitively balanced map with analysis
     */
    public function generateCompetitiveMap(int $rows, int $cols, int $expectedPlayers = 2): array
    {
        $options = [
            'clustering_iterations' => self::COMPETITIVE_CLUSTERING_ITERATIONS,
            'fix_small_clusters' => true,
            'min_cluster_size' => self::COMPETITIVE_MIN_CLUSTER_SIZE
        ];

        $result = $this->generateBalancedMap($rows, $cols, $options);

        // Additional competitive validation
        $competitiveAnalysis = $this->analyzeCompetitiveBalance($result['map'], $rows, $cols, $expectedPlayers);

        return [
            'map' => $result['map'],
            'validation' => $result['validation'],
            'statistics' => $result['statistics'],
            'competitive_analysis' => $competitiveAnalysis
        ];
    }

    /**
     * Generates a map with specific terrain emphasis
     *
     * Creates a map that emphasizes certain terrain types while maintaining balance.
     * Useful for themed scenarios or specific gameplay mechanics.
     *
     * @param int $rows Number of rows in the map grid
     * @param int $cols Number of columns in the map grid
     * @param array $terrainEmphasis Terrain types to emphasize with multipliers
     * @return array Themed map with terrain analysis
     */
    public function generateThemedMap(int $rows, int $cols, array $terrainEmphasis = []): array
    {
        // TODO: Future implementation would modify terrain weights before generation
        // For now, use standard generation with post-processing analysis

        $map = $this->generateMap($rows, $cols);
        $statistics = $this->getTerrainStatistics($map, $rows, $cols);

        return [
            'map' => $map,
            'statistics' => $statistics,
            'theme_analysis' => $this->analyzeThemeCompliance($statistics, $terrainEmphasis)
        ];
    }

    // Domain-focused convenience methods

    /**
     * Gets terrain generation statistics for analysis
     */
    public function getTerrainStatistics(array $map, int $rows, int $cols): array
    {
        return $this->validationService->calculateTerrainStatistics($map, $rows, $cols);
    }

    /**
     * Validates map for game balance
     */
    public function validateMap(array $map, int $rows, int $cols): array
    {
        return $this->validationService->validateMapBalance($map, $rows, $cols);
    }

    /**
     * Analyzes map for strategic elements
     */
    public function analyzeStrategicElements(array $map, int $rows, int $cols): array
    {
        $statistics = $this->getTerrainStatistics($map, $rows, $cols);

        return [
            'defensive_terrain_percentage' => $this->calculateDefensiveTerrainPercentage($statistics),
            'movement_restriction_percentage' => $this->calculateMovementRestrictionPercentage($statistics),
            'resource_terrain_percentage' => $this->calculateResourceTerrainPercentage($statistics),
            'mobility_terrain_percentage' => $statistics[TerrainType::PLAINS->value] ?? 0,
            'strategic_balance_score' => $this->calculateStrategicBalanceScore($statistics)
        ];
    }

    /**
     * Gets terrain clustering configuration for external analysis
     */
    public function getClusteringConfiguration(): array
    {
        return $this->clusteringService->getClusteringConfiguration();
    }

    /**
     * Gets terrain compatibility matrix for external analysis
     */
    public function getCompatibilityMatrix(): array
    {
        return $this->smoothingService->getCompatibilityMatrix();
    }

    /**
     * Gets terrain generation weights for external analysis
     */
    public function getTerrainWeights(): array
    {
        return $this->baseTerrainService->getTerrainWeights();
    }

    /**
     * Provides recommendations for map improvement
     */
    public function getMapImprovementRecommendations(array $map, int $rows, int $cols): array
    {
        $validation = $this->validateMap($map, $rows, $cols);
        $strategicAnalysis = $this->analyzeStrategicElements($map, $rows, $cols);

        $recommendations = [];

        if (!$validation['isValid']) {
            $recommendations['balance_issues'] = $validation['suggestions'];
        }

        if ($strategicAnalysis['strategic_balance_score'] < self::STRATEGIC_BALANCE_THRESHOLD) {
            $recommendations['strategic_improvements'] = [
                'Consider more diverse terrain distribution',
                'Add more defensive terrain for strategic depth',
                'Ensure adequate movement corridors'
            ];
        }

        return $recommendations;
    }

    // Private helper methods

    /**
     * Calculates defensive terrain percentage
     */
    private function calculateDefensiveTerrainPercentage(array $statistics): float
    {
        return ($statistics[TerrainType::MOUNTAIN->value] ?? 0) +
            ($statistics[TerrainType::FOREST->value] ?? 0);
    }

    /**
     * Calculates movement restriction terrain percentage
     */
    private function calculateMovementRestrictionPercentage(array $statistics): float
    {
        return ($statistics[TerrainType::WATER->value] ?? 0) +
            ($statistics[TerrainType::SWAMP->value] ?? 0);
    }

    /**
     * Calculates resource terrain percentage
     */
    private function calculateResourceTerrainPercentage(array $statistics): float
    {
        return ($statistics[TerrainType::FOREST->value] ?? 0) +
            ($statistics[TerrainType::MOUNTAIN->value] ?? 0);
    }

    /**
     * Analyzes competitive balance for multiplayer scenarios
     */
    private function analyzeCompetitiveBalance(array $map, int $rows, int $cols, int $expectedPlayers): array
    {
        $statistics = $this->getTerrainStatistics($map, $rows, $cols);
        $strategicAnalysis = $this->analyzeStrategicElements($map, $rows, $cols);

        return [
            'player_balance_score' => $this->calculatePlayerBalanceScore($statistics, $expectedPlayers),
            'strategic_depth_score' => $strategicAnalysis['strategic_balance_score'],
            'terrain_variety_score' => $this->calculateTerrainVarietyScore($statistics),
            'movement_freedom_score' => $strategicAnalysis['mobility_terrain_percentage'] / 100,
            'overall_competitive_score' => $this->calculateOverallCompetitiveScore($statistics, $strategicAnalysis)
        ];
    }

    /**
     * Analyzes theme compliance for themed maps
     */
    private function analyzeThemeCompliance(array $statistics, array $terrainEmphasis): array
    {
        $compliance = [];

        foreach ($terrainEmphasis as $terrain => $expectedPercentage) {
            $actualPercentage = $statistics[$terrain] ?? 0;
            $compliance[$terrain] = [
                'expected' => $expectedPercentage,
                'actual' => $actualPercentage,
                'compliance_score' => min(1.0, $actualPercentage / max(1, $expectedPercentage))
            ];
        }

        return $compliance;
    }

    /**
     * Calculates strategic balance score (0.0 to 1.0)
     */
    private function calculateStrategicBalanceScore(array $statistics): float
    {
        // Ideal percentages for strategic balance
        $idealBalance = [
            TerrainType::PLAINS->value => 35,
            TerrainType::FOREST->value => 25,
            TerrainType::MOUNTAIN->value => 15,
            TerrainType::WATER->value => 10,
            TerrainType::DESERT->value => 10,
            TerrainType::SWAMP->value => 5
        ];

        $totalDeviation = 0;
        foreach ($idealBalance as $terrain => $idealPercentage) {
            $actualPercentage = $statistics[$terrain] ?? 0;
            $totalDeviation += abs($idealPercentage - $actualPercentage);
        }

        // Convert deviation to score (lower deviation = higher score)
        return max(0, 1 - ($totalDeviation / self::MAX_DEVIATION));
    }

    /**
     * Calculates player balance score for multiplayer
     */
    private function calculatePlayerBalanceScore(array $statistics, int $expectedPlayers): float
    {
        // More players need more mobility and resources
        $mobilityWeight = min(1.0, $expectedPlayers / self::MAX_EXPECTED_PLAYERS);
        $mobilityScore = ($statistics[TerrainType::PLAINS->value] ?? 0) / self::TARGET_MOBILITY_PERCENTAGE;

        return min(1.0, $mobilityScore * $mobilityWeight + (1 - $mobilityWeight) * 0.7);
    }

    /**
     * Calculates terrain variety score
     */
    private function calculateTerrainVarietyScore(array $statistics): float
    {
        $terrainTypes = count($statistics);
        $maxVariety = count(TerrainType::cases());

        // Penalize if any terrain type dominates too much
        $maxPercentage = max($statistics);
        $dominancePenalty = $maxPercentage > self::MAX_TERRAIN_DOMINANCE ? self::DOMINANCE_PENALTY : 1.0;

        return ($terrainTypes / $maxVariety) * $dominancePenalty;
    }

    /**
     * Calculates overall competitive score
     */
    private function calculateOverallCompetitiveScore(array $statistics, array $strategicAnalysis): float
    {
        $balanceScore = $this->calculateStrategicBalanceScore($statistics);
        $varietyScore = $this->calculateTerrainVarietyScore($statistics);
        $mobilityScore = $strategicAnalysis['mobility_terrain_percentage'] / self::TARGET_MOBILITY_PERCENTAGE;

        return ($balanceScore + $varietyScore + min(1.0, $mobilityScore)) / 3;
    }
}
