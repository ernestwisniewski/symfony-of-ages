<?php

namespace App\Application\Map\Controller;

use App\Application\Map\Exception\MapAnalysisException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * GetMapController handles map data retrieval operations
 *
 * Responsible for serving map data to the frontend JavaScript application,
 * managing map configuration and data transformation for client consumption.
 */
class GetMapController extends AbstractMapController
{
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
        try {
            $mapData = $this->getOrGenerateMapData($session);
            $transformedData = $this->transformMapDataForClient($mapData);

            $this->logger->debug("Map data retrieved successfully", [
                'rows' => self::ROWS,
                'cols' => self::COLS,
                'total_tiles' => count($mapData) * count($mapData[0])
            ]);

            return $this->json([
                'config' => $this->createMapConfig(),
                'data' => $transformedData
            ]);

        } catch (\Throwable $e) {
            $wrappedException = MapAnalysisException::mapDataCorrupted();
            return $this->handleException($wrappedException, 'map data retrieval');
        }
    }
} 