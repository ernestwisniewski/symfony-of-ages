<?php

namespace App\Application\Map\Controller;

use App\Application\Map\Service\MapGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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
     * Generates and returns map data in JSON format for the frontend.
     * Transforms the internal map data structure to a format suitable
     * for client-side consumption with terrain types and properties.
     * Includes map configuration information (rows, cols, size).
     *
     * @return JsonResponse JSON response containing the transformed map data and configuration
     */
    #[Route('/api/map-data', name: 'api_map_data')]
    public function getMapData(): JsonResponse
    {
        $mapData = $this->mapGenerator->generateMap(self::ROWS, self::COLS);
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
}
