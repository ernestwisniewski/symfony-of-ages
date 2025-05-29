<?php

namespace App\Application\Player\Controller;

use App\Application\Player\Service\PlayerService;
use App\Application\Map\Service\MapGenerator;
use App\Domain\Player\ValueObject\Position;
use App\Domain\Player\Entity\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * PlayerController handles player-related API endpoints
 *
 * Manages player creation, movement commands, and game state persistence
 * through session storage. Coordinates between PlayerService and frontend
 * to provide complete player management functionality.
 */
class PlayerController extends AbstractController
{
    /** @var int Number of columns in the hex grid */
    const int COLS = 100;

    /** @var int Number of rows in the hex grid */
    const int ROWS = 100;

    public function __construct(
        private readonly PlayerService $playerService,
        private readonly MapGenerator $mapGenerator
    ) {
    }

    /**
     * Creates a new player with random starting position
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

        // Generate map data for position validation
        $mapData = $this->mapGenerator->generateMap(self::ROWS, self::COLS);

        // Create player with random starting position
        $player = $this->playerService->createPlayer(
            $playerName,
            self::ROWS,
            self::COLS,
            $mapData
        );

        // Store player in session
        $session->set('player', $player->toArray());
        $session->set('mapData', $mapData);

        return $this->json([
            'success' => true,
            'player' => $player->toArray(),
            'message' => "Player {$playerName} created at position {$player->getPosition()}"
        ]);
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
            return $this->json([
                'success' => false,
                'message' => 'No player found. Create a player first.'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'player' => $playerData
        ]);
    }

    /**
     * Moves player to target position
     *
     * @param Request $request HTTP request containing target coordinates
     * @param SessionInterface $session Session for player and map data
     * @return JsonResponse Movement result
     */
    #[Route('/api/player/move', name: 'api_player_move', methods: ['POST'])]
    public function movePlayer(Request $request, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $playerData = $session->get('player');
        $mapData = $session->get('mapData');

        if (!$playerData || !$mapData) {
            return $this->json([
                'success' => false,
                'message' => 'No player or map data found. Create a player first.'
            ], 404);
        }

        // Reconstruct player from session data
        $currentPosition = Position::fromArray($playerData['position']);
        $player = new Player(
            $playerData['id'],
            $currentPosition,
            $playerData['name'],
            $playerData['maxMovementPoints'],
            $playerData['color']
        );

        // Restore movement points
        $player = $this->restorePlayerMovementPoints($player, $playerData);

        // Create target position
        $targetPosition = new Position($data['row'], $data['col']);

        // Attempt movement
        $result = $this->playerService->movePlayer($player, $targetPosition, $mapData);

        if ($result['success']) {
            // Update session with new player state
            $session->set('player', $player->toArray());
        }

        return $this->json($result);
    }

    /**
     * Starts a new turn for the player (restores movement points)
     *
     * @param SessionInterface $session Session containing player data
     * @return JsonResponse New turn status
     */
    #[Route('/api/player/new-turn', name: 'api_player_new_turn', methods: ['POST'])]
    public function startNewTurn(SessionInterface $session): JsonResponse
    {
        $playerData = $session->get('player');

        if (!$playerData) {
            return $this->json([
                'success' => false,
                'message' => 'No player found. Create a player first.'
            ], 404);
        }

        // Reconstruct player
        $position = Position::fromArray($playerData['position']);
        $player = new Player(
            $playerData['id'],
            $position,
            $playerData['name'],
            $playerData['maxMovementPoints'],
            $playerData['color']
        );

        // Start new turn
        $this->playerService->startPlayerTurn($player);

        // Update session
        $session->set('player', $player->toArray());

        return $this->json([
            'success' => true,
            'player' => $player->toArray(),
            'message' => 'New turn started. Movement points restored.'
        ]);
    }

    /**
     * Restores movement points from session data
     */
    private function restorePlayerMovementPoints(Player $player, array $playerData): Player
    {
        // This is a bit hacky - in a real app we'd have proper persistence
        $reflection = new \ReflectionClass($player);
        $movementPointsProperty = $reflection->getProperty('movementPoints');
        $movementPointsProperty->setAccessible(true);
        $movementPointsProperty->setValue($player, $playerData['movementPoints']);
        
        return $player;
    }
} 