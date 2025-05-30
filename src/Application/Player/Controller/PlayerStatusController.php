<?php

namespace App\Application\Player\Controller;

use App\Application\Player\Exception\PlayerServiceException;
use App\Domain\Player\Exception\PlayerNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * PlayerStatusController handles player status and analysis operations
 *
 * Responsible for providing comprehensive player status information,
 * tactical analysis, and strategic recommendations.
 */
class PlayerStatusController extends AbstractPlayerController
{
    /**
     * Gets comprehensive player status
     *
     * @param SessionInterface $session Session containing player data
     * @return JsonResponse Player status with tactical information
     */
    #[Route('/api/player/status', name: 'api_player_status', methods: ['GET'])]
    public function getPlayerStatus(SessionInterface $session): JsonResponse
    {
        try {
            $player = $this->getPlayerFromSession($session);

            $this->logger->debug("Retrieving player status", [
                'player_id' => $player->getId()->getValue()
            ]);

            $status = $this->playerService->getPlayerStatus($player);

            $this->logger->debug("Player status retrieved successfully", [
                'player_id' => $player->getId()->getValue(),
                'movement_points' => $player->getMovementPoints(),
                'can_continue' => $player->canContinueTurn()
            ]);

            return $this->json([
                'success' => true,
                'player_status' => $status
            ]);

        } catch (PlayerNotFoundException | PlayerServiceException $e) {
            return $this->handleException($e, 'player status retrieval');
        } catch (\Throwable $e) {
            $wrappedException = PlayerServiceException::statusRetrievalFailed($e->getMessage(), $e);
            return $this->handleException($wrappedException, 'player status retrieval');
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
        try {
            $player = $this->getPlayerFromSession($session);
            $mapData = $this->getOrGenerateMapData($session);

            $this->logger->info("Performing tactical analysis", [
                'player_id' => $player->getId()->getValue(),
                'position' => $player->getPosition()->toArray()
            ]);

            $tacticalAnalysis = $this->playerService->analyzePlayerTacticalSituation(
                $player,
                $mapData,
                self::ROWS,
                self::COLS
            );

            $this->logger->info("Tactical analysis completed", [
                'player_id' => $player->getId()->getValue(),
                'recommendations_count' => count($tacticalAnalysis['recommendations'] ?? [])
            ]);

            return $this->json([
                'success' => true,
                'tactical_analysis' => $tacticalAnalysis
            ]);

        } catch (PlayerNotFoundException | PlayerServiceException $e) {
            return $this->handleException($e, 'tactical analysis');
        } catch (\Throwable $e) {
            $wrappedException = PlayerServiceException::tacticalAnalysisFailed($e->getMessage(), $e);
            return $this->handleException($wrappedException, 'tactical analysis');
        }
    }
} 