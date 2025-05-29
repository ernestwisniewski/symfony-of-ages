<?php

namespace App\Application\Map\Controller;

use App\Application\Map\Service\MapGenerator;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * MapController handles the main game interface and map data API
 *
 * Provides routes for displaying the game interface and serving map data
 * to the frontend JavaScript application. Manages the hexagonal map
 * configuration and data transformation for client consumption.
 */
class MapController extends AbstractController
{
    /** @var int Number of columns in the hex grid */
    const int COLS = 100;

    /** @var int Number of rows in the hex grid */
    const int ROWS = 100;

    /** @var int Size (radius) of individual hexagons in pixels */
    const int SIZE = 58;

    /**
     * @param MapGenerator $mapGenerator Service for generating random map data
     */
    public function __construct(
        private readonly MapGenerator $mapGenerator
    )
    {
    }

    /**
     * API endpoint for retrieving map data
     *
     * Uses session-stored map data when available (if player was created),
     * otherwise generates new map data. Transforms the internal map data
     * structure to a format suitable for client-side consumption with terrain
     * types and properties. Includes map configuration information (rows, cols, size).
     *
     * @param SessionInterface $session Session for retrieving stored map data
     * @return JsonResponse JSON response containing the transformed map data and configuration
     */
    #[Route('/api/map-data', name: 'api_map_data')]
    public function getMapData(SessionInterface $session): JsonResponse
    {
        // Use existing map data from session if available (player was created)
        $mapData = $session->get('mapData');

        if (!$mapData) {
            // Generate new map data if none exists in session
            $mapData = $this->mapGenerator->generateMap(self::ROWS, self::COLS);
            $session->set('mapData', $mapData);
            error_log("Generated new map data for frontend (no session data found)");
        } else {
            error_log("Using existing map data from session for frontend");
        }

        $transformedData = $this->transformMapDataForClient($mapData);

        return $this->json([
            'config' => [
                'rows' => self::ROWS,
                'cols' => self::COLS,
                'size' => self::SIZE
            ],
            'data' => $transformedData
        ]);
    }

    /**
     * Transforms internal map data structure for client consumption
     *
     * @param array $mapData Internal map data from MapGenerator
     * @return array Transformed data suitable for frontend
     */
    private function transformMapDataForClient(array $mapData): array
    {
        return array_map(function ($row) {
            return array_map(function ($tile) {
                return [
                    'type' => $tile['type'],
                    'name' => $tile['name'],
                    'properties' => [
                        'color' => $tile['properties']['color'],
                        'movement' => $tile['properties']['movementCost'],
                        'defense' => $tile['properties']['defense'],
                        'resources' => $tile['properties']['resources']
                    ]
                ];
            }, $row);
        }, $mapData);
    }

    /**
     * Analyzes map for strategic elements and balance
     *
     * @param SessionInterface $session Session for retrieving stored map data
     * @return JsonResponse Strategic analysis of the current map
     */
    #[Route('/api/map-analysis', name: 'api_map_analysis')]
    public function getMapAnalysis(SessionInterface $session): JsonResponse
    {
        // Use existing map data from session if available
        $mapData = $session->get('mapData');

        if (!$mapData) {
            // Generate new map data if none exists in session
            $mapData = $this->mapGenerator->generateMap(self::ROWS, self::COLS);
            $session->set('mapData', $mapData);
        }

        try {
            // Get comprehensive map analysis
            $statistics = $this->mapGenerator->getTerrainStatistics($mapData, self::ROWS, self::COLS);
            $validation = $this->mapGenerator->validateMap($mapData, self::ROWS, self::COLS);
            $strategicAnalysis = $this->mapGenerator->analyzeStrategicElements($mapData, self::ROWS, self::COLS);
            $recommendations = $this->mapGenerator->getMapImprovementRecommendations($mapData, self::ROWS, self::COLS);

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

        } catch (Exception $e) {
            error_log("Error analyzing map: " . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Failed to analyze map'
            ], 500);
        }
    }

    /**
     * Generates a competitive map optimized for multiplayer gameplay
     *
     * @param Request $request Request with optional parameters
     * @param SessionInterface $session Session for storing map data
     * @return JsonResponse Generated competitive map with analysis
     */
    #[Route('/api/generate-competitive-map', name: 'api_generate_competitive_map', methods: ['POST'])]
    public function generateCompetitiveMap(Request $request, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $expectedPlayers = $data['expected_players'] ?? 2;

        if ($expectedPlayers < 1 || $expectedPlayers > 8) {
            return $this->json([
                'success' => false,
                'message' => 'Expected players must be between 1 and 8'
            ], 400);
        }

        try {
            $result = $this->mapGenerator->generateCompetitiveMap(self::ROWS, self::COLS, $expectedPlayers);

            // Store in session
            $session->set('mapData', $result['map']);

            $transformedData = $this->transformMapDataForClient($result['map']);

            return $this->json([
                'success' => true,
                'config' => [
                    'rows' => self::ROWS,
                    'cols' => self::COLS,
                    'size' => self::SIZE,
                    'expected_players' => $expectedPlayers
                ],
                'data' => $transformedData,
                'analysis' => [
                    'validation' => $result['validation'],
                    'statistics' => $result['statistics'],
                    'competitive_analysis' => $result['competitive_analysis']
                ]
            ]);

        } catch (Exception $e) {
            error_log("Error generating competitive map: " . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Failed to generate competitive map'
            ], 500);
        }
    }

    /**
     * Generates a themed map with specific terrain emphasis
     *
     * @param Request $request Request with terrain emphasis configuration
     * @param SessionInterface $session Session for storing map data
     * @return JsonResponse Generated themed map with analysis
     */
    #[Route('/api/generate-themed-map', name: 'api_generate_themed_map', methods: ['POST'])]
    public function generateThemedMap(Request $request, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $terrainEmphasis = $data['terrain_emphasis'] ?? [];

        // Validate terrain emphasis
        $validTerrains = ['plains', 'forest', 'mountain', 'water', 'desert', 'swamp'];
        foreach ($terrainEmphasis as $terrain => $percentage) {
            if (!in_array($terrain, $validTerrains)) {
                return $this->json([
                    'success' => false,
                    'message' => "Invalid terrain type: {$terrain}"
                ], 400);
            }

            if ($percentage < 0 || $percentage > 100) {
                return $this->json([
                    'success' => false,
                    'message' => "Terrain percentage must be between 0 and 100"
                ], 400);
            }
        }

        try {
            $result = $this->mapGenerator->generateThemedMap(self::ROWS, self::COLS, $terrainEmphasis);

            // Store in session
            $session->set('mapData', $result['map']);

            $transformedData = $this->transformMapDataForClient($result['map']);

            return $this->json([
                'success' => true,
                'config' => [
                    'rows' => self::ROWS,
                    'cols' => self::COLS,
                    'size' => self::SIZE,
                    'terrain_emphasis' => $terrainEmphasis
                ],
                'data' => $transformedData,
                'analysis' => [
                    'statistics' => $result['statistics'],
                    'theme_analysis' => $result['theme_analysis']
                ]
            ]);

        } catch (Exception $e) {
            error_log("Error generating themed map: " . $e->getMessage());
            return $this->json([
                'success' => false,
                'message' => 'Failed to generate themed map'
            ], 500);
        }
    }
}
