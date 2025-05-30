<?php

namespace App\Domain\Game\Service;

use App\Domain\Game\Entity\Game;
use App\Domain\Game\Repository\GameRepositoryInterface;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameSettings;
use App\Domain\Game\ValueObject\GameStatus;
use App\Domain\Player\ValueObject\PlayerId;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * GameManagementService - Domain service for high-level game operations
 *
 * Coordinates complex game operations that involve multiple aggregates
 * or require sophisticated business logic. Acts as a bridge between
 * application services and domain entities.
 *
 * Responsibilities:
 * - Game matchmaking and lobby management
 * - Game lifecycle coordination
 * - Complex business rules spanning multiple entities
 * - Game state validation and consistency
 */
class GameManagementService
{
    public function __construct(
        private readonly GameRepositoryInterface $gameRepository
    )
    {
    }

    /**
     * Creates a new game with specified settings
     */
    public function createGame(string $gameName, ?GameSettings $settings = null): Game
    {
        $gameId = GameId::generate();
        $game = Game::create($gameId, $gameName, $settings);

        $this->gameRepository->save($game);

        return $game;
    }

    /**
     * Creates a quick play game for immediate start
     */
    public function createQuickPlayGame(string $gameName = 'Quick Play'): Game
    {
        $gameId = GameId::generate();
        $game = Game::createQuickPlay($gameId, $gameName);

        $this->gameRepository->save($game);

        return $game;
    }

    /**
     * Finds an available game for a player to join
     */
    public function findAvailableGameForPlayer(PlayerId $playerId): ?Game
    {
        // First check if player is already in any active game
        $existingGames = $this->gameRepository->findByPlayer($playerId);
        $activeGame = array_find(
            $existingGames,
            fn(Game $game) => $game->isInProgress() || $game->getStatus() === GameStatus::WAITING_FOR_PLAYERS
        );

        if ($activeGame) {
            return $activeGame; // Player is already in an active game
        }

        // Find games waiting for players
        $waitingGames = $this->gameRepository->findGamesWaitingForPlayers();

        // Return first available game that can accept players
        return array_find($waitingGames, fn(Game $game) => $game->canAcceptPlayers()) ?? null;
    }

    /**
     * Joins a player to a specific game
     */
    public function joinPlayerToGame(PlayerId $playerId, GameId $gameId): Game
    {
        $game = $this->gameRepository->findById($gameId);

        if (!$game) {
            throw new InvalidArgumentException("Game with ID {$gameId->value} not found");
        }

        $game->addPlayer($playerId);
        $this->gameRepository->save($game);

        return $game;
    }

    /**
     * Removes a player from a game
     */
    public function removePlayerFromGame(PlayerId $playerId, GameId $gameId): Game
    {
        $game = $this->gameRepository->findById($gameId);

        if (!$game) {
            throw new InvalidArgumentException("Game with ID {$gameId->value} not found");
        }

        $game->removePlayer($playerId);
        $this->gameRepository->save($game);

        return $game;
    }

    /**
     * Starts a game if conditions are met
     */
    public function startGame(GameId $gameId): Game
    {
        $game = $this->gameRepository->findById($gameId);

        if (!$game) {
            throw new InvalidArgumentException("Game with ID {$gameId->value} not found");
        }

        $game->start();
        $this->gameRepository->save($game);

        return $game;
    }

    /**
     * Ends a game with optional reason
     */
    public function endGame(GameId $gameId, ?string $reason = null): Game
    {
        $game = $this->gameRepository->findById($gameId);

        if (!$game) {
            throw new InvalidArgumentException("Game with ID {$gameId->value} not found");
        }

        $game->end($reason);
        $this->gameRepository->save($game);

        return $game;
    }

    /**
     * Advances to next turn in a game
     */
    public function advanceToNextTurn(GameId $gameId): Game
    {
        $game = $this->gameRepository->findById($gameId);

        if (!$game) {
            throw new InvalidArgumentException("Game with ID {$gameId->value} not found");
        }

        $game->nextTurn();
        $this->gameRepository->save($game);

        return $game;
    }

    /**
     * Performs matchmaking to find or create a suitable game
     */
    public function performMatchmaking(PlayerId $playerId, ?GameSettings $preferredSettings = null): Game
    {
        // Try to find existing suitable game
        $availableGame = $this->findAvailableGameForPlayer($playerId);

        if ($availableGame && !$availableGame->hasPlayer($playerId)) {
            $availableGame->addPlayer($playerId);
            $this->gameRepository->save($availableGame);
            return $availableGame;
        }

        // Create new game if no suitable game found
        $settings = $preferredSettings ?? GameSettings::createDefault();
        $game = $this->createGame('Matchmaking Game', $settings);
        $game->addPlayer($playerId);
        $this->gameRepository->save($game);

        return $game;
    }

    /**
     * Gets comprehensive game statistics
     */
    public function getGameStatistics(): array
    {
        $totalGames = $this->gameRepository->count();
        $activeGames = count($this->gameRepository->findActiveGames());
        $waitingGames = count($this->gameRepository->findGamesWaitingForPlayers());
        $inProgressGames = $this->gameRepository->countByStatus(GameStatus::IN_PROGRESS);
        $endedGames = $this->gameRepository->countByStatus(GameStatus::ENDED);

        return [
            'total_games' => $totalGames,
            'active_games' => $activeGames,
            'waiting_for_players' => $waitingGames,
            'in_progress' => $inProgressGames,
            'ended' => $endedGames,
            'completion_rate' => $totalGames > 0 ? round(($endedGames / $totalGames) * 100, 2) : 0
        ];
    }

    /**
     * Validates if a player can perform an action in a game
     */
    public function canPlayerPerformAction(PlayerId $playerId, GameId $gameId, string $action): bool
    {
        $game = $this->gameRepository->findById($gameId);

        if (!$game || !$game->hasPlayer($playerId)) {
            return false;
        }

        return match ($action) {
            'move' => $game->isInProgress() && $game->isCurrentPlayer($playerId),
            'leave' => !$game->isFinished(),
            'start' => $game->canStart(),
            'end' => $game->isInProgress(),
            default => false
        };
    }

    /**
     * Cleans up finished games older than specified days
     */
    public function cleanupOldGames(int $daysOld = 30): int
    {
        $cutoffDate = new DateTimeImmutable("-{$daysOld} days");
        $oldGames = $this->gameRepository->findGamesCreatedSince(
            new DateTimeImmutable('1970-01-01'),
            $cutoffDate
        );

        $cleanedCount = 0;
        foreach ($oldGames as $game) {
            if ($game->isFinished()) {
                $this->gameRepository->remove($game->getId());
                $cleanedCount++;
            }
        }

        return $cleanedCount;
    }
}
