<?php

namespace App\Application\Player\Controller;

use App\Application\Player\Exception\PlayerServiceException;
use App\Domain\Player\Exception\InvalidPlayerDataException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * CreatePlayerController handles player creation operations
 *
 * Responsible for creating new players with random starting positions
 * and managing the initial game setup process.
 */
class CreatePlayerController extends AbstractPlayerController
{
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
        try {
            $data = json_decode($request->getContent(), true);
            
            // Validate JSON input
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->createErrorResponse('Invalid JSON: ' . json_last_error_msg(), 400);
            }
            
            $playerName = $data['name'] ?? 'Player';

            // Generate map data once and store in session for consistency
            $mapData = $this->getOrGenerateMapData($session);

            // Create player with random starting position using the facade
            $player = $this->playerService->createPlayer(
                $playerName,
                self::ROWS,
                self::COLS,
                $mapData
            );

            $position = $player->getPosition();
            $this->logger->info("Player created successfully", [
                'player_id' => $player->getId()->getValue(),
                'player_name' => $player->getName(),
                'position' => ['row' => $position->getRow(), 'col' => $position->getCol()]
            ]);

            // Validate that the hex position is valid
            $terrain = null;
            if ($position->getRow() >= 0 && $position->getRow() < self::ROWS &&
                $position->getCol() >= 0 && $position->getCol() < self::COLS) {
                $terrain = $mapData[$position->getRow()][$position->getCol()];
                $this->logger->debug("Player placed on terrain", [
                    'terrain' => $terrain['name'],
                    'position' => ['row' => $position->getRow(), 'col' => $position->getCol()]
                ]);
            } else {
                $this->logger->warning("Player position is outside map bounds", [
                    'position' => ['row' => $position->getRow(), 'col' => $position->getCol()],
                    'map_bounds' => ['rows' => self::ROWS, 'cols' => self::COLS]
                ]);
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

        } catch (InvalidPlayerDataException $e) {
            return $this->createErrorResponse('Invalid player data: ' . $e->getMessage(), 400);
        } catch (PlayerServiceException $e) {
            return $this->handleException($e, 'player creation');
        } catch (\Throwable $e) {
            $wrappedException = PlayerServiceException::creationFailed($e->getMessage(), $e);
            return $this->handleException($wrappedException, 'player creation');
        }
    }
} 