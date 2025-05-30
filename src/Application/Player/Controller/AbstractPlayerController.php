<?php

namespace App\Application\Player\Controller;

use App\Application\Map\Service\MapGenerator;
use App\Application\Player\Exception\PlayerServiceException;
use App\Application\Player\Service\PlayerService;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Exception\PlayerNotFoundException;
use App\Domain\Shared\ValueObject\MapConfiguration;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Throwable;

/**
 * AbstractPlayerController provides common functionality for player-related controllers
 *
 * Contains shared services and helper methods used across
 * multiple player controllers following DRY principle.
 */
abstract class AbstractPlayerController extends AbstractController
{
    /** @var string Standard player not found message */
    protected const string PLAYER_NOT_FOUND_MESSAGE = 'No player found. Create a player first.';

    public function __construct(
        protected readonly PlayerService   $playerService,
        protected readonly MapGenerator    $mapGenerator,
        protected readonly LoggerInterface $logger
    ) {
    }

    /**
     * Gets or generates map data, storing it in session for consistency
     *
     * Uses the refactored MapGenerator facade which orchestrates between
     * specialized map generation services while maintaining backward compatibility.
     */
    protected function getOrGenerateMapData(SessionInterface $session): array
    {
        $mapData = $session->get('mapData');

        if (!$mapData) {
            // Generate new map data using the facade
            $mapData = $this->mapGenerator->generateMap(MapConfiguration::ROWS, MapConfiguration::COLS);
            $session->set('mapData', $mapData);
            $this->logger->info("Generated new map data and stored in session");
        } else {
            $this->logger->debug("Using existing map data from session for frontend");
        }

        return $mapData;
    }

    /**
     * Gets player from session with proper error handling
     *
     * @param SessionInterface $session Session containing player data
     * @return Player Player instance
     * @throws PlayerNotFoundException When player is not found in session
     * @throws PlayerServiceException When player data is corrupted
     */
    protected function getPlayerFromSession(SessionInterface $session): Player
    {
        $playerData = $session->get('player');

        if (!$playerData) {
            throw PlayerNotFoundException::inSession();
        }

        try {
            return Player::fromArray($playerData);
        } catch (Throwable $e) {
            $this->logger->error("Error reconstructing player from session", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw PlayerServiceException::sessionDataCorrupted($e);
        }
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
            $exception instanceof PlayerNotFoundException =>
            $this->createErrorResponse($exception->getMessage(), 404),
            $exception instanceof PlayerServiceException =>
            $this->createErrorResponse($exception->getMessage(), 500),
            default =>
            $this->createErrorResponse('An unexpected error occurred', 500)
        };
    }
}
