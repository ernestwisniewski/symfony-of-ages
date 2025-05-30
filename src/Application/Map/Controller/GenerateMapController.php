<?php

namespace App\Application\Map\Controller;

use App\Application\Map\Exception\MapGenerationException;
use App\Domain\Shared\ValueObject\MapConfiguration;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * GenerateMapController handles map generation operations
 *
 * Responsible for generating different types of maps including competitive
 * and themed maps with specific configurations and analysis.
 */
class GenerateMapController extends AbstractMapController
{
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
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $expectedPlayers = $data['expected_players'] ?? 2;

            if ($expectedPlayers < 1 || $expectedPlayers > 8) {
                throw MapGenerationException::invalidPlayerCount($expectedPlayers);
            }

            $this->logger->info("Generating competitive map", [
                'expected_players' => $expectedPlayers,
                'rows' => MapConfiguration::ROWS,
                'cols' => MapConfiguration::COLS
            ]);

            $result = $this->mapGenerator->generateCompetitiveMap(MapConfiguration::ROWS, MapConfiguration::COLS, $expectedPlayers);

            // Store in session
            $session->set('mapData', $result['map']);

            $transformedData = $this->transformMapDataForClient($result['map']);

            $this->logger->info("Competitive map generated successfully", [
                'expected_players' => $expectedPlayers,
                'validation_passed' => $result['validation']['isValid'] ?? false
            ]);

            return $this->json([
                'success' => true,
                'config' => $this->createMapConfig(['expected_players' => $expectedPlayers]),
                'data' => $transformedData,
                'analysis' => [
                    'validation' => $result['validation'],
                    'statistics' => $result['statistics'],
                    'competitive_analysis' => $result['competitive_analysis']
                ]
            ]);

        } catch (MapGenerationException $e) {
            return $this->handleException($e, 'competitive map generation');
        } catch (Throwable $e) {
            $wrappedException = MapGenerationException::competitiveMapFailed($expectedPlayers ?? 2, $e);
            return $this->handleException($wrappedException, 'competitive map generation');
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
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $terrainEmphasis = $data['terrain_emphasis'] ?? [];

            // Validate terrain emphasis
            $this->validateTerrainEmphasis($terrainEmphasis);

            $this->logger->info("Generating themed map", [
                'terrain_emphasis' => $terrainEmphasis,
                'rows' => MapConfiguration::ROWS,
                'cols' => MapConfiguration::COLS
            ]);

            $result = $this->mapGenerator->generateThemedMap(MapConfiguration::ROWS, MapConfiguration::COLS, $terrainEmphasis);

            // Store in session
            $session->set('mapData', $result['map']);

            $transformedData = $this->transformMapDataForClient($result['map']);

            $this->logger->info("Themed map generated successfully", [
                'terrain_emphasis' => $terrainEmphasis
            ]);

            return $this->json([
                'success' => true,
                'config' => $this->createMapConfig(['terrain_emphasis' => $terrainEmphasis]),
                'data' => $transformedData,
                'analysis' => [
                    'statistics' => $result['statistics'],
                    'theme_analysis' => $result['theme_analysis']
                ]
            ]);

        } catch (MapGenerationException $e) {
            return $this->handleException($e, 'themed map generation');
        } catch (Throwable $e) {
            $wrappedException = MapGenerationException::themedMapFailed($terrainEmphasis ?? [], $e);
            return $this->handleException($wrappedException, 'themed map generation');
        }
    }

    /**
     * Validates terrain emphasis configuration
     *
     * @param array $terrainEmphasis Terrain emphasis configuration
     * @throws MapGenerationException If validation fails
     */
    private function validateTerrainEmphasis(array $terrainEmphasis): void
    {
        $validTerrains = ['plains', 'forest', 'mountain', 'water', 'desert', 'swamp'];

        // Check if any terrain is invalid using PHP 8.4 array_any
        $hasInvalidTerrain = array_any(
            array_keys($terrainEmphasis),
            fn($terrain) => !in_array($terrain, $validTerrains)
        );

        if ($hasInvalidTerrain) {
            // Find the first invalid terrain for error message
            $invalidTerrain = array_find(
                array_keys($terrainEmphasis),
                fn($terrain) => !in_array($terrain, $validTerrains)
            );
            throw MapGenerationException::invalidTerrainEmphasis($invalidTerrain, $terrainEmphasis[$invalidTerrain]);
        }

        // Check if any percentage is invalid using PHP 8.4 array_any
        $hasInvalidPercentage = array_any(
            $terrainEmphasis,
            fn($percentage) => $percentage < 0 || $percentage > 100
        );

        if ($hasInvalidPercentage) {
            // Find the first invalid percentage for error message
            $invalidEntry = array_find(
                $terrainEmphasis,
                fn($percentage, $terrain) => $percentage < 0 || $percentage > 100,
                ARRAY_FILTER_USE_BOTH
            );
            $terrain = array_search($invalidEntry, $terrainEmphasis);
            throw MapGenerationException::invalidTerrainEmphasis($terrain, $invalidEntry);
        }
    }
}
