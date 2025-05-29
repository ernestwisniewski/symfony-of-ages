<?php

namespace App\Application\Player\Service;

use App\Domain\Player\Entity\Player;

/**
 * PlayerTurnService handles player turn management and turn-related operations
 *
 * Responsible for starting new turns, managing turn state, and handling
 * turn-related mechanics. Follows Single Responsibility Principle by
 * focusing only on turn management logic.
 */
class PlayerTurnService
{
    /**
     * Starts a new turn for the player
     *
     * Restores movement points and performs any other turn-start operations.
     *
     * @param Player $player Player to start turn for
     * @return void
     */
    public function startPlayerTurn(Player $player): void
    {
        $player->startNewTurn();
    }

    /**
     * Ends the current turn for the player
     *
     * Performs any end-of-turn cleanup or operations.
     *
     * @param Player $player Player to end turn for
     * @return void
     */
    public function endPlayerTurn(Player $player): void
    {
        // Future: Add end-of-turn logic here
        // For now, just a placeholder for future expansion
    }

    /**
     * Checks if player has any movement points remaining
     *
     * @param Player $player Player to check
     * @return bool True if player can still move
     */
    public function canPlayerContinueTurn(Player $player): bool
    {
        return $player->getMovementPoints() > 0;
    }

    /**
     * Gets remaining movement points for the player
     *
     * @param Player $player Player to check
     * @return int Number of movement points remaining
     */
    public function getRemainingMovementPoints(Player $player): int
    {
        return $player->getMovementPoints();
    }

    /**
     * Gets maximum movement points for the player
     *
     * @param Player $player Player to check
     * @return int Maximum movement points per turn
     */
    public function getMaxMovementPoints(Player $player): int
    {
        return $player->getMaxMovementPoints();
    }

    /**
     * Checks if player turn is effectively over (no movement points)
     *
     * @param Player $player Player to check
     * @return bool True if turn should end
     */
    public function shouldEndTurn(Player $player): bool
    {
        return !$this->canPlayerContinueTurn($player);
    }
} 