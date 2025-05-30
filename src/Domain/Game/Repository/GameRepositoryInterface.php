<?php

namespace App\Domain\Game\Repository;

use App\Domain\Game\Entity\Game;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameStatus;
use App\Domain\Player\ValueObject\PlayerId;
use DateTimeImmutable;

/**
 * Repository interface for Game aggregate
 *
 * Defines data persistence operations for the Game aggregate root.
 * Follows Repository pattern to abstract data access concerns from
 * the domain layer, allowing for different storage implementations.
 */
interface GameRepositoryInterface
{
    /**
     * Saves or updates a game
     *
     * @param Game $game Game to save
     * @return void
     */
    public function save(Game $game): void;

    /**
     * Finds a game by its ID
     *
     * @param GameId $id Game ID to search for
     * @return Game|null Game if found, null otherwise
     */
    public function findById(GameId $id): ?Game;

    /**
     * Finds a game by its name
     *
     * @param string $name Game name to search for
     * @return Game|null Game if found, null otherwise
     */
    public function findByName(string $name): ?Game;

    /**
     * Finds games by status
     *
     * @param GameStatus $status Game status to filter by
     * @return Game[] Array of games with the specified status
     */
    public function findByStatus(GameStatus $status): array;

    /**
     * Finds games containing a specific player
     *
     * @param PlayerId $playerId Player ID to search for
     * @return Game[] Array of games containing the player
     */
    public function findByPlayer(PlayerId $playerId): array;

    /**
     * Finds active games (in progress or waiting for players)
     *
     * @return Game[] Array of active games
     */
    public function findActiveGames(): array;

    /**
     * Finds games waiting for players
     *
     * @return Game[] Array of games that can accept new players
     */
    public function findGamesWaitingForPlayers(): array;

    /**
     * Gets all games
     *
     * @return Game[] Array of all games
     */
    public function findAll(): array;

    /**
     * Removes a game
     *
     * @param GameId $id Game ID to remove
     * @return void
     */
    public function remove(GameId $id): void;

    /**
     * Checks if a game exists
     *
     * @param GameId $id Game ID to check
     * @return bool True if game exists
     */
    public function exists(GameId $id): bool;

    /**
     * Counts total number of games
     *
     * @return int Number of games
     */
    public function count(): int;

    /**
     * Counts games by status
     *
     * @param GameStatus $status Status to count
     * @return int Number of games with the specified status
     */
    public function countByStatus(GameStatus $status): int;

    /**
     * Finds games created within a time period
     *
     * @param DateTimeImmutable $since Start date
     * @param DateTimeImmutable|null $until End date (null for current time)
     * @return Game[] Array of games created in the time period
     */
    public function findGamesCreatedSince(DateTimeImmutable $since, ?DateTimeImmutable $until = null): array;

    /**
     * Finds the most recent game for a player
     *
     * @param PlayerId $playerId Player ID to search for
     * @return Game|null Most recent game for the player, null if none found
     */
    public function findMostRecentGameForPlayer(PlayerId $playerId): ?Game;
}
