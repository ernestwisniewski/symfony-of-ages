<?php

namespace App\Application\Player\Controller;

use App\Application\Player\Exception\PlayerServiceException;
use App\Domain\Player\Exception\PlayerNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * PlayerTurnController handles player turn management operations
 *
 * Responsible for starting new turns, managing turn state,
 * and handling turn-related game mechanics.
 */
class PlayerTurnController extends AbstractPlayerController
{
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
        try {
            $player = $this->getPlayerFromSession($session);

            $this->logger->info("Starting new turn for player", [
                'player_id' => $player->getId()->getValue(),
                'current_movement_points' => $player->getMovementPoints()
            ]);

            // Start new turn using the facade (delegates to PlayerTurnService)
            $this->playerService->startPlayerTurn($player);

            // Update session
            $session->set('player', $player->toArray());

            $this->logger->info("New turn started successfully", [
                'player_id' => $player->getId()->getValue(),
                'restored_movement_points' => $player->getMovementPoints()
            ]);

            return $this->json([
                'success' => true,
                'player' => $player->toArray(),
                'message' => 'New turn started. Movement points restored.'
            ]);

        } catch (PlayerNotFoundException | PlayerServiceException $e) {
            return $this->handleException($e, 'new turn start');
        } catch (\Throwable $e) {
            $wrappedException = PlayerServiceException::statusRetrievalFailed($e->getMessage(), $e);
            return $this->handleException($wrappedException, 'new turn start');
        }
    }
} 