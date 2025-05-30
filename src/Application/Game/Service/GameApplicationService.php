<?php

namespace App\Application\Game\Service;

use App\Domain\Game\Entity\Game;
use App\Domain\Game\Repository\GameRepositoryInterface;
use App\Domain\Game\Service\GameManagementService;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameSettings;
use App\Domain\Player\ValueObject\PlayerId;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * GameApplicationService - Application layer service for game operations
 *
 * Orchestrates game-related operations by coordinating between domain services,
 * repositories, and external services. Provides a clean API for controllers
 * and handles application concerns like logging, validation, and error handling.
 */
class GameApplicationService
{
    public function __construct(
        private readonly GameManagementService   $gameManagementService,
        private readonly GameRepositoryInterface $gameRepository,
        private readonly LoggerInterface         $logger
    )
    {
    }

    /**
     * Creates a new game with the specified settings
     */
    public function createGame(string $gameName, ?GameSettings $settings = null): array
    {
        try {
            $this->logger->info('Creating new game', ['name' => $gameName]);

            $game = $this->gameManagementService->createGame($gameName, $settings);

            $this->logger->info('Game created successfully', [
                'game_id' => $game->getId()->value,
                'name' => $game->getName()
            ]);

            return [
                'success' => true,
                'game' => $game->toArray(),
                'message' => 'Game created successfully'
            ];

        } catch (Exception $e) {
            $this->logger->error('Failed to create game', [
                'name' => $gameName,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Joins a player to an existing game or finds suitable game
     */
    public function joinGame(string $playerIdValue, ?string $gameIdValue = null): array
    {
        try {
            $playerId = new PlayerId($playerIdValue);

            if ($gameIdValue) {
                // Join specific game
                $gameId = new GameId($gameIdValue);
                $game = $this->gameManagementService->joinPlayerToGame($playerId, $gameId);

                $this->logger->info('Player joined specific game', [
                    'player_id' => $playerIdValue,
                    'game_id' => $gameIdValue
                ]);
            } else {
                // Perform matchmaking
                $game = $this->gameManagementService->performMatchmaking($playerId);

                $this->logger->info('Player matched to game', [
                    'player_id' => $playerIdValue,
                    'game_id' => $game->getId()->value
                ]);
            }

            return [
                'success' => true,
                'game' => $game->toArray(),
                'message' => 'Successfully joined game'
            ];

        } catch (Exception $e) {
            $this->logger->error('Failed to join game', [
                'player_id' => $playerIdValue,
                'game_id' => $gameIdValue,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Starts a game if conditions are met
     */
    public function startGame(string $gameIdValue): array
    {
        try {
            $gameId = new GameId($gameIdValue);
            $game = $this->gameManagementService->startGame($gameId);

            $this->logger->info('Game started', [
                'game_id' => $gameIdValue,
                'player_count' => $game->getPlayerCount()
            ]);

            return [
                'success' => true,
                'game' => $game->toArray(),
                'message' => 'Game started successfully'
            ];

        } catch (Exception $e) {
            $this->logger->error('Failed to start game', [
                'game_id' => $gameIdValue,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Leaves a game
     */
    public function leaveGame(string $playerIdValue, string $gameIdValue): array
    {
        try {
            $playerId = new PlayerId($playerIdValue);
            $gameId = new GameId($gameIdValue);

            $game = $this->gameManagementService->removePlayerFromGame($playerId, $gameId);

            $this->logger->info('Player left game', [
                'player_id' => $playerIdValue,
                'game_id' => $gameIdValue
            ]);

            return [
                'success' => true,
                'game' => $game->toArray(),
                'message' => 'Successfully left game'
            ];

        } catch (Exception $e) {
            $this->logger->error('Failed to leave game', [
                'player_id' => $playerIdValue,
                'game_id' => $gameIdValue,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Advances to the next turn in a game
     */
    public function nextTurn(string $gameIdValue): array
    {
        try {
            $gameId = new GameId($gameIdValue);
            $game = $this->gameManagementService->advanceToNextTurn($gameId);

            $this->logger->info('Turn advanced', [
                'game_id' => $gameIdValue,
                'turn_number' => $game->getCurrentTurnNumber(),
                'current_player' => $game->getCurrentPlayerId()?->value
            ]);

            return [
                'success' => true,
                'game' => $game->toArray(),
                'message' => 'Turn advanced successfully'
            ];

        } catch (Exception $e) {
            $this->logger->error('Failed to advance turn', [
                'game_id' => $gameIdValue,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Gets game information
     */
    public function getGame(string $gameIdValue): array
    {
        try {
            $gameId = new GameId($gameIdValue);
            $game = $this->gameRepository->findById($gameId);

            if (!$game) {
                return [
                    'success' => false,
                    'message' => 'Game not found'
                ];
            }

            return [
                'success' => true,
                'game' => $game->toArray()
            ];

        } catch (Exception $e) {
            $this->logger->error('Failed to get game', [
                'game_id' => $gameIdValue,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Lists available games for joining
     */
    public function getAvailableGames(): array
    {
        try {
            $games = $this->gameRepository->findGamesWaitingForPlayers();

            return [
                'success' => true,
                'games' => array_map(fn(Game $game) => $game->toArray(), $games),
                'count' => count($games)
            ];

        } catch (Exception $e) {
            $this->logger->error('Failed to get available games', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Gets comprehensive game statistics
     */
    public function getGameStatistics(): array
    {
        try {
            $statistics = $this->gameManagementService->getGameStatistics();

            return [
                'success' => true,
                'statistics' => $statistics
            ];

        } catch (Exception $e) {
            $this->logger->error('Failed to get game statistics', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Validates if a player can perform a specific action in a game
     */
    public function canPlayerPerformAction(string $playerIdValue, string $gameIdValue, string $action): array
    {
        try {
            $playerId = new PlayerId($playerIdValue);
            $gameId = new GameId($gameIdValue);

            $canPerform = $this->gameManagementService->canPlayerPerformAction($playerId, $gameId, $action);

            return [
                'success' => true,
                'can_perform' => $canPerform,
                'action' => $action
            ];

        } catch (Exception $e) {
            $this->logger->error('Failed to validate player action', [
                'player_id' => $playerIdValue,
                'game_id' => $gameIdValue,
                'action' => $action,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
