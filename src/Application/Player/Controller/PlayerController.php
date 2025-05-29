<?php

namespace App\Application\Player\Controller;

use App\Application\Map\Service\MapGenerator;
use App\Application\Player\Service\PlayerService;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\ValueObject\Position;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * PlayerController handles player-related API endpoints
 *
 * Manages player creation, movement commands, and game state persistence
 * through session storage. Coordinates with the refactored PlayerService
 * facade and uses updated DDD structures.
 */
class PlayerController extends AbstractController
{
    /** @var int Number of columns in the hex grid */
    private const int COLS = 100;

    /** @var int Number of rows in the hex grid */
    private const int ROWS = 100;

    /** @var string Standard player not found message */
    private const string PLAYER_NOT_FOUND_MESSAGE = 'No player found. Create a player first.';

    public function __construct(
        private readonly PlayerService $playerService,
        private readonly MapGenerator  $mapGenerator
    )
    {
    }

    /**
     * Creates a new player with random starting position
     *
     * Uses the refactored PlayerService facade which orchestrates between
     * specialized services and now uses proper DDD value objects and domain events.
     *
     * @param Request $request HTTP request containing player name
     * @param SessionInterface $session Session for storing player data
     * @return JsonResponse Player data and starting position
     */
    #[Route('/api/player/create', name: 'api_player_create', methods: ['POST'])]
    public function createPlayer(Request $request, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $playerName = $data['name'] ?? 'Player';

        // Generate map data once and store in session for consistency
        $mapData = $this->getOrGenerateMapData($session);

        try {
            // Create player with random starting position using the facade
            $player = $this->playerService->createPlayer(
                $playerName,
                self::ROWS,
                self::COLS,
                $mapData
            );

            $position = $player->getPosition();
            error_log("Player created at hex position ({$position->getRow()}, {$position->getCol()})");

            // Validate that the hex position is valid
            $terrain = null;
            if ($position->getRow() >= 0 && $position->getRow() < self::ROWS &&
                $position->getCol() >= 0 && $position->getCol() < self::COLS) {
                $terrain = $mapData[$position->getRow()][$position->getCol()];
                error_log("Player placed on terrain: {$terrain['name']} at ({$position->getRow()}, {$position->getCol()})");
            } else {
                error_log("WARNING: Player position is outside map bounds!");
            }

            // Store player in session (for now, until proper persistence is implemented)
            $session->set('player', $player->toArray());

            // Clear domain events after handling (in a real app, these would be published to event bus)
            $events = $player->getDomainEvents();
            $player->clearDomainEvents();

            $terrainName = $terrain ? $terrain['name'] : 'unknown terrain';
            return $this->json([
                'success' => true,
                'player' => $player->toArray(),
                'message' => "Player {$playerName} created at hex ({$position->getRow()}, {$position->getCol()}) on {$terrainName}",
                'events' => count($events) // For debugging purposes
            ]);

        } catch (InvalidArgumentException $e) {
            return $this->createErrorResponse('Invalid player data: ' . $e->getMessage(), 400);
        } catch (Exception $e) {
            error_log("Error creating player: " . $e->getMessage());
            return $this->createErrorResponse('Failed to create player', 500);
        }
    }

    /**
     * Gets current player data
     *
     * @param SessionInterface $session Session containing player data
     * @return JsonResponse Current player state
     */
    #[Route('/api/player', name: 'api_player_get', methods: ['GET'])]
    public function getPlayer(SessionInterface $session): JsonResponse
    {
        $playerData = $session->get('player');

        if (!$playerData) {
            return $this->createErrorResponse(self::PLAYER_NOT_FOUND_MESSAGE, 404);
        }

        return $this->json([
            'success' => true,
            'player' => $playerData
        ]);
    }

    /**
     * Moves player to target position
     *
     * Uses the refactored PlayerService facade which delegates to
     * PlayerMovementService and now properly handles domain events.
     *
     * @param Request $request HTTP request containing target coordinates
     * @param SessionInterface $session Session for player data
     * @return JsonResponse Movement result
     */
    #[Route('/api/player/move', name: 'api_player_move', methods: ['POST'])]
    public function movePlayer(Request $request, SessionInterface $session): JsonResponse
    {
        $playerData = $session->get('player');

        if (!$playerData) {
            return $this->createErrorResponse(self::PLAYER_NOT_FOUND_MESSAGE, 404);
        }

        try {
            $data = json_decode($request->getContent(), true);
            $mapData = $this->getOrGenerateMapData($session);
            $player = Player::fromArray($playerData);
            $targetPosition = new Position($data['row'], $data['col']);

            // Attempt movement using the facade (delegates to PlayerMovementService)
            $result = $this->playerService->movePlayer($player, $targetPosition, $mapData);

            if ($result['success']) {
                // Update session with new player state
                $session->set('player', $player->toArray());

                // Clear domain events after handling
                $player->clearDomainEvents();
            }

            return $this->json($result);

        } catch (InvalidArgumentException $e) {
            return $this->createErrorResponse('Invalid movement data: ' . $e->getMessage(), 400);
        } catch (Exception $e) {
            error_log("Error moving player: " . $e->getMessage());
            return $this->createErrorResponse('Movement failed', 500);
        }
    }

    /**
     * Starts a new turn for the player (restores movement points)
     *
     * Uses the refactored PlayerService facade which delegates to
     * PlayerTurnService for turn management.
     *
     * @param SessionInterface $session Session containing player data
     * @return JsonResponse New turn status
     */
    #[Route('/api/player/new-turn', name: 'api_player_new_turn', methods: ['POST'])]
    public function startNewTurn(SessionInterface $session): JsonResponse
    {
        $player = $this->getPlayerFromSession($session);

        if ($player instanceof JsonResponse) {
            return $player; // Error response
        }

        try {
            // Start new turn using the facade (delegates to PlayerTurnService)
            $this->playerService->startPlayerTurn($player);

            // Update session
            $session->set('player', $player->toArray());

            return $this->json([
                'success' => true,
                'player' => $player->toArray(),
                'message' => 'New turn started. Movement points restored.'
            ]);

        } catch (Exception $e) {
            error_log("Error starting new turn: " . $e->getMessage());
            return $this->createErrorResponse('Failed to start new turn', 500);
        }
    }

    /**
     * Gets comprehensive player status
     *
     * @param SessionInterface $session Session containing player data
     * @return JsonResponse Player status with tactical information
     */
    #[Route('/api/player/status', name: 'api_player_status', methods: ['GET'])]
    public function getPlayerStatus(SessionInterface $session): JsonResponse
    {
        $player = $this->getPlayerFromSession($session);

        if ($player instanceof JsonResponse) {
            return $player; // Error response
        }

        try {
            $status = $this->playerService->getPlayerStatus($player);

            return $this->json([
                'success' => true,
                'player_status' => $status
            ]);

        } catch (Exception $e) {
            error_log("Error getting player status: " . $e->getMessage());
            return $this->createErrorResponse('Failed to get player status', 500);
        }
    }

    /**
     * Gets tactical analysis for the player's current situation
     *
     * @param SessionInterface $session Session containing player data
     * @return JsonResponse Tactical analysis and recommendations
     */
    #[Route('/api/player/tactical-analysis', name: 'api_player_tactical_analysis', methods: ['GET'])]
    public function getTacticalAnalysis(SessionInterface $session): JsonResponse
    {
        $player = $this->getPlayerFromSession($session);

        if ($player instanceof JsonResponse) {
            return $player; // Error response
        }

        try {
            $mapData = $this->getOrGenerateMapData($session);

            $tacticalAnalysis = $this->playerService->analyzePlayerTacticalSituation(
                $player,
                $mapData,
                self::ROWS,
                self::COLS
            );

            return $this->json([
                'success' => true,
                'tactical_analysis' => $tacticalAnalysis
            ]);

        } catch (Exception $e) {
            error_log("Error getting tactical analysis: " . $e->getMessage());
            return $this->createErrorResponse('Failed to analyze tactical situation', 500);
        }
    }

    /**
     * Validates a specific position for player operations
     *
     * @param Request $request HTTP request containing position coordinates
     * @param SessionInterface $session Session for map data
     * @return JsonResponse Position validation result
     */
    #[Route('/api/player/validate-position', name: 'api_player_validate_position', methods: ['POST'])]
    public function validatePosition(Request $request, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['row']) || !isset($data['col'])) {
            return $this->createErrorResponse('Row and column coordinates are required', 400);
        }

        try {
            $mapData = $this->getOrGenerateMapData($session);
            $position = new Position($data['row'], $data['col']);

            $validation = $this->playerService->validatePlayerPosition(
                $position,
                $mapData,
                self::ROWS,
                self::COLS
            );

            return $this->json([
                'success' => true,
                'validation' => $validation
            ]);

        } catch (InvalidArgumentException $e) {
            return $this->createErrorResponse('Invalid position data: ' . $e->getMessage(), 400);
        } catch (Exception $e) {
            error_log("Error validating position: " . $e->getMessage());
            return $this->createErrorResponse('Failed to validate position', 500);
        }
    }

    // Private helper methods

    /**
     * Gets or generates map data, storing it in session for consistency
     *
     * Uses the refactored MapGenerator facade which orchestrates between
     * specialized map generation services while maintaining backward compatibility.
     */
    private function getOrGenerateMapData(SessionInterface $session): array
    {
        $mapData = $session->get('mapData');

        if (!$mapData) {
            // Generate new map data using the facade
            $mapData = $this->mapGenerator->generateMap(self::ROWS, self::COLS);
            $session->set('mapData', $mapData);
            error_log("Generated new map data and stored in session");
        }

        return $mapData;
    }

    /**
     * Gets player from session with proper error handling
     *
     * @param SessionInterface $session Session containing player data
     * @return Player|JsonResponse Player instance or error response
     */
    private function getPlayerFromSession(SessionInterface $session): Player|JsonResponse
    {
        $playerData = $session->get('player');

        if (!$playerData) {
            return $this->createErrorResponse(self::PLAYER_NOT_FOUND_MESSAGE, 404);
        }

        try {
            return Player::fromArray($playerData);
        } catch (Exception $e) {
            error_log("Error reconstructing player from session: " . $e->getMessage());
            return $this->createErrorResponse('Invalid player data in session', 500);
        }
    }

    /**
     * Creates a standardized error response
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @return JsonResponse Error response
     */
    private function createErrorResponse(string $message, int $statusCode = 500): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }
}
