<?php

namespace App\Application\Player\Controller;

use App\Application\Player\Exception\PlayerServiceException;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Exception\PlayerNotFoundException;
use App\Domain\Shared\ValueObject\MapConfiguration;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * GetPlayerController handles player data retrieval operations
 *
 * Responsible for fetching current player state, calculating possible moves,
 * and providing detailed movement analysis for the frontend.
 */
class GetPlayerController extends AbstractPlayerController
{
    /**
     * Gets current player data with possible moves
     *
     * @param SessionInterface $session Session containing player data
     * @return JsonResponse Current player state with calculated possible moves
     */
    #[Route('/api/player', name: 'api_player_get', methods: ['GET'])]
    public function getPlayer(SessionInterface $session): JsonResponse
    {
        try {
            $playerData = $session->get('player');

            if (!$playerData) {
                throw PlayerNotFoundException::inSession();
            }

            $player = Player::fromArray($playerData);
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

            $this->logger->debug("Player data retrieved successfully", [
                'player_id' => $player->id,
                'possible_moves_count' => count($possibleMoves)
            ]);

            return $this->json([
                'success' => true,
                'player' => $playerData,
                'possibleMoves' => $possibleMoves,
                'movementOptions' => $movementOptions
            ]);

        } catch (PlayerNotFoundException $e) {
            return $this->handleException($e, 'player data retrieval');
        } catch (Throwable $e) {
            $wrappedException = PlayerServiceException::movementCalculationFailed($e->getMessage(), $e);
            return $this->handleException($wrappedException, 'player data retrieval');
        }
    }
}
