<?php

namespace App\Application\Player\Controller;

use App\Application\Player\Exception\PlayerServiceException;
use App\Domain\Player\Exception\InvalidPlayerDataException;
use App\Domain\Player\Exception\PlayerNotFoundException;
use App\Domain\Player\ValueObject\Position;
use App\Domain\Shared\ValueObject\MapConfiguration;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * MovePlayerController handles player movement operations
 *
 * Responsible for executing player movements, validating movement possibilities,
 * and managing movement-related game mechanics.
 */
class MovePlayerController extends AbstractPlayerController
{
    /**
     * Gets possible moves for the current player
     *
     * @param SessionInterface $session Session containing player data
     * @return JsonResponse Possible moves with detailed information
     */
    #[Route('/api/player/possible-moves', name: 'api_player_possible_moves', methods: ['GET'])]
    public function getPlayerPossibleMoves(SessionInterface $session): JsonResponse
    {
        try {
            $player = $this->getPlayerFromSession($session);
            $mapData = $this->getOrGenerateMapData($session);

            // Calculate possible moves for player
            $possibleMoves = $this->playerService->calculatePlayerPossibleMoves(
                $player,
                $mapData,
                MapConfiguration::ROWS,
                MapConfiguration::COLS
            );

            // Calculate detailed movement options
            $movementOptions = $this->playerService->calculatePlayerMovementOptions(
                $player,
                $mapData,
                MapConfiguration::ROWS,
                MapConfiguration::COLS
            );

            $this->logger->debug("Possible moves calculated", [
                'player_id' => $player->getId()->getValue(),
                'moves_count' => count($possibleMoves)
            ]);

            return $this->json([
                'success' => true,
                'currentPosition' => $player->getPosition()->toArray(),
                'currentMovementPoints' => $player->getMovementPoints(),
                'maxMovementPoints' => $player->getMaxMovementPoints(),
                'possibleMoves' => $possibleMoves,
                'movementOptions' => $movementOptions
            ]);

        } catch (PlayerNotFoundException|PlayerServiceException $e) {
            return $this->handleException($e, 'possible moves calculation');
        } catch (Throwable $e) {
            $wrappedException = PlayerServiceException::movementCalculationFailed($e->getMessage(), $e);
            return $this->handleException($wrappedException, 'possible moves calculation');
        }
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
        try {
            $data = json_decode($request->getContent(), true);

            // Validate JSON input
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->createErrorResponse('Invalid JSON: ' . json_last_error_msg(), 400);
            }

            if (!isset($data['row']) || !isset($data['col'])) {
                return $this->createErrorResponse('Row and column coordinates are required', 400);
            }

            $mapData = $this->getOrGenerateMapData($session);
            $player = $this->getPlayerFromSession($session);
            $targetPosition = new Position($data['row'], $data['col']);

            $this->logger->info("Attempting player movement", [
                'player_id' => $player->getId()->getValue(),
                'from' => $player->getPosition()->toArray(),
                'to' => $targetPosition->toArray()
            ]);

            // Attempt movement using the facade (delegates to PlayerMovementService)
            $result = $this->playerService->movePlayer($player, $targetPosition, $mapData);

            if ($result['success']) {
                // Update session with new player state
                $session->set('player', $player->toArray());

                // Clear domain events after handling
                $player->clearDomainEvents();

                $this->logger->info("Player movement successful", [
                    'player_id' => $player->getId()->getValue(),
                    'new_position' => $targetPosition->toArray(),
                    'remaining_movement' => $player->getMovementPoints()
                ]);
            } else {
                $this->logger->warning("Player movement failed", [
                    'player_id' => $player->getId()->getValue(),
                    'reason' => $result['message'] ?? 'Unknown reason'
                ]);
            }

            return $this->json($result);

        } catch (InvalidPlayerDataException $e) {
            return $this->createErrorResponse('Invalid movement data: ' . $e->getMessage(), 400);
        } catch (PlayerNotFoundException|PlayerServiceException $e) {
            return $this->handleException($e, 'player movement');
        } catch (Throwable $e) {
            $wrappedException = PlayerServiceException::movementCalculationFailed($e->getMessage(), $e);
            return $this->handleException($wrappedException, 'player movement');
        }
    }

    /**
     * Checks if player can move to specific position
     *
     * @param Request $request HTTP request containing target coordinates
     * @param SessionInterface $session Session for player data
     * @return JsonResponse Movement possibility result
     */
    #[Route('/api/player/can-move-to', name: 'api_player_can_move_to', methods: ['POST'])]
    public function canPlayerMoveTo(Request $request, SessionInterface $session): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validate JSON input
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->createErrorResponse('Invalid JSON: ' . json_last_error_msg(), 400);
            }

            if (!isset($data['row']) || !isset($data['col'])) {
                return $this->createErrorResponse('Row and column coordinates are required', 400);
            }

            $player = $this->getPlayerFromSession($session);
            $mapData = $this->getOrGenerateMapData($session);
            $targetPosition = new Position($data['row'], $data['col']);

            $movementCheck = $this->playerService->canPlayerMoveToSpecificPosition(
                $player,
                $targetPosition,
                $mapData
            );

            $this->logger->debug("Movement possibility checked", [
                'player_id' => $player->getId()->getValue(),
                'target_position' => $targetPosition->toArray(),
                'can_move' => $movementCheck['canMove']
            ]);

            return $this->json([
                'success' => true,
                'targetPosition' => $targetPosition->toArray(),
                'canMove' => $movementCheck['canMove'],
                'movementAnalysis' => $movementCheck
            ]);

        } catch (InvalidPlayerDataException $e) {
            return $this->createErrorResponse('Invalid position data: ' . $e->getMessage(), 400);
        } catch (PlayerNotFoundException|PlayerServiceException $e) {
            return $this->handleException($e, 'movement possibility check');
        } catch (Throwable $e) {
            $wrappedException = PlayerServiceException::movementCalculationFailed($e->getMessage(), $e);
            return $this->handleException($wrappedException, 'movement possibility check');
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
        try {
            $data = json_decode($request->getContent(), true);

            // Validate JSON input
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->createErrorResponse('Invalid JSON: ' . json_last_error_msg(), 400);
            }

            if (!isset($data['row']) || !isset($data['col'])) {
                return $this->createErrorResponse('Row and column coordinates are required', 400);
            }

            $mapData = $this->getOrGenerateMapData($session);
            $position = new Position($data['row'], $data['col']);

            $validation = $this->playerService->validatePlayerPosition(
                $position,
                $mapData,
                MapConfiguration::ROWS,
                MapConfiguration::COLS
            );

            $this->logger->debug("Position validated", [
                'position' => $position->toArray(),
                'valid' => $validation['valid']
            ]);

            return $this->json([
                'success' => true,
                'validation' => $validation
            ]);

        } catch (InvalidPlayerDataException $e) {
            return $this->createErrorResponse('Invalid position data: ' . $e->getMessage(), 400);
        } catch (PlayerServiceException $e) {
            return $this->handleException($e, 'position validation');
        } catch (Throwable $e) {
            $wrappedException = PlayerServiceException::statusRetrievalFailed($e->getMessage(), $e);
            return $this->handleException($wrappedException, 'position validation');
        }
    }
}
