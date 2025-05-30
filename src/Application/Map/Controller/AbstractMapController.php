<?php

namespace App\Application\Map\Controller;

use App\Application\Map\Exception\MapAnalysisException;
use App\Application\Map\Exception\MapApplicationException;
use App\Application\Map\Exception\MapGenerationException;
use App\Application\Map\Service\MapGenerator;
use App\Domain\Shared\ValueObject\MapConfiguration;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Throwable;

/**
 * AbstractMapController provides common functionality for map-related controllers
 *
 * Contains shared services and helper methods used across
 * multiple map controllers following DRY principle.
 */
abstract class AbstractMapController extends AbstractController
{
    public function __construct(
        protected readonly MapGenerator    $mapGenerator,
        protected readonly LoggerInterface $logger
    ) {
    }

    /**
     * Gets or generates map data, storing it in session for consistency
     *
     * @param SessionInterface $session Session for retrieving/storing map data
     * @return array Map data
     * @throws MapGenerationException When map generation fails
     */
    protected function getOrGenerateMapData(SessionInterface $session): array
    {
        $mapData = $session->get('mapData');

        if (!$mapData) {
            try {
                // Generate new map data if none exists in session
                $mapData = $this->mapGenerator->generateMap(MapConfiguration::ROWS, MapConfiguration::COLS);
                $session->set('mapData', $mapData);
                $this->logger->info("Generated new map data for frontend", [
                    'rows' => MapConfiguration::ROWS,
                    'cols' => MapConfiguration::COLS
                ]);
            } catch (Throwable $e) {
                $this->logger->error("Failed to generate map data", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw MapGenerationException::standardMapFailed(MapConfiguration::ROWS, MapConfiguration::COLS, $e);
            }
        } else {
            $this->logger->debug("Using existing map data from session for frontend");
        }

        return $mapData;
    }

    /**
     * Transforms internal map data structure for client consumption
     *
     * @param array $mapData Internal map data from MapGenerator
     * @return array Transformed data suitable for frontend
     */
    protected function transformMapDataForClient(array $mapData): array
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
     * Creates a standardized error response
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @return JsonResponse Error response
     */
    protected function createErrorResponse(string $message, int $statusCode = 500): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }

    /**
     * Creates a standard map configuration array
     *
     * @param array $additionalConfig Additional configuration parameters
     * @return array Map configuration
     */
    protected function createMapConfig(array $additionalConfig = []): array
    {
        return MapConfiguration::getConfig($additionalConfig);
    }

    /**
     * Handles exceptions and returns appropriate JSON response
     *
     * @param Throwable $exception Exception to handle
     * @param string $context Context information for logging
     * @return JsonResponse Error response
     */
    protected function handleException(Throwable $exception, string $context): JsonResponse
    {
        $this->logger->error("Exception in {$context}", [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        return match (true) {
            $exception instanceof MapGenerationException =>
            $this->createErrorResponse($exception->getMessage(), 400),
            $exception instanceof MapAnalysisException, $exception instanceof MapApplicationException =>
            $this->createErrorResponse($exception->getMessage(), 500),
            default =>
            $this->createErrorResponse('An unexpected error occurred', 500)
        };
    }
}
