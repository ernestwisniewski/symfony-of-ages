<?php

namespace App\Domain\Player\Repository;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Player\ValueObject\Position;

/**
 * PlayerRepositoryInterface defines the contract for player persistence
 *
 * Following DDD Repository pattern to abstract persistence concerns
 * from the domain layer. The interface is in the domain layer
 * while implementations belong to infrastructure.
 */
interface PlayerRepositoryInterface
{
    /**
     * Saves or updates a player
     *
     * @param Player $player Player to save
     * @return void
     */
    public function save(Player $player): void;

    /**
     * Finds a player by their ID
     *
     * @param PlayerId $id Player ID to search for
     * @return Player|null Player if found, null otherwise
     */
    public function findById(PlayerId $id): ?Player;

    /**
     * Finds a player by their name
     *
     * @param string $name Player name to search for
     * @return Player|null Player if found, null otherwise
     */
    public function findByName(string $name): ?Player;

    /**
     * Finds players at specific position
     *
     * @param Position $position Position to search
     * @return Player[] Array of players at the position
     */
    public function findByPosition(Position $position): array;

    /**
     * Gets all players
     *
     * @return Player[] Array of all players
     */
    public function findAll(): array;

    /**
     * Removes a player
     *
     * @param PlayerId $id Player ID to remove
     * @return void
     */
    public function remove(PlayerId $id): void;

    /**
     * Checks if a player exists
     *
     * @param PlayerId $id Player ID to check
     * @return bool True if player exists
     */
    public function exists(PlayerId $id): bool;

    /**
     * Counts total number of players
     *
     * @return int Number of players
     */
    public function count(): int;
}
