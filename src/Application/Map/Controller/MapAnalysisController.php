<?php

namespace App\Application\Map\Controller;

use App\Application\Map\Exception\MapAnalysisException;
use App\Domain\Shared\ValueObject\MapConfiguration;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * MapAnalysisController handles map analysis operations
 *
 * Responsible for analyzing maps for strategic elements, balance validation,
 * and providing detailed terrain statistics and recommendations.
 */
class MapAnalysisController extends AbstractMapController
{
    /**
     * Analyzes map for strategic elements and balance
     *
     * @param SessionInterface $session Session for retrieving stored map data
     * @return JsonResponse Strategic analysis of the current map
     */
    #[Route('/api/map-analysis', name: 'api_map_analysis')]
    public function getMapAnalysis(SessionInterface $session): JsonResponse
    {
        try {
            $mapData = $this->getOrGenerateMapData($session);

            $this->logger->info("Starting comprehensive map analysis", [
                'rows' => MapConfiguration::ROWS,
                'cols' => MapConfiguration::COLS
            ]);

            // Get comprehensive map analysis
            $statistics = $this->mapGenerator->getTerrainStatistics($mapData, MapConfiguration::ROWS, MapConfiguration::COLS);
            $validation = $this->mapGenerator->validateMap($mapData, MapConfiguration::ROWS, MapConfiguration::COLS);
            $strategicAnalysis = $this->mapGenerator->analyzeStrategicElements($mapData, MapConfiguration::ROWS, MapConfiguration::COLS);
            $recommendations = $this->mapGenerator->getMapImprovementRecommendations($mapData, MapConfiguration::ROWS, MapConfiguration::COLS);

            $this->logger->info("Map analysis completed successfully", [
                'validation_passed' => $validation['isValid'] ?? false,
                'terrain_types_count' => count($statistics),
                'recommendations_count' => count($recommendations)
            ]);

            return $this->json([
                'success' => true,
                'analysis' => [
                    'terrain_statistics' => $statistics,
                    'balance_validation' => $validation,
                    'strategic_elements' => $strategicAnalysis,
                    'improvement_recommendations' => $recommendations,
                    'configuration' => [
                        'terrain_weights' => $this->mapGenerator->getTerrainWeights(),
                        'clustering_config' => $this->mapGenerator->getClusteringConfiguration(),
                        'compatibility_matrix' => $this->mapGenerator->getCompatibilityMatrix()
                    ]
                ]
            ]);

        } catch (MapAnalysisException $e) {
            return $this->handleException($e, 'map analysis');
        } catch (Throwable $e) {
            $wrappedException = MapAnalysisException::strategicAnalysisFailed($e);
            return $this->handleException($wrappedException, 'map analysis');
        }
    }
}
