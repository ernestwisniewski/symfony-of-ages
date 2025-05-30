<?php

namespace App\Domain\Player\Service;

use App\Domain\Player\Entity\Player;

/**
 * PlayerTurnDomainService handles player turn domain logic
 *
 * Pure domain service that encapsulates business rules related to
 * player turns, movement point restoration, and turn validation.
 * Contains no infrastructure dependencies.
 */
class PlayerTurnDomainService
{
    /**
     * Starts a new turn for the player
     *
     * Applies domain business rules for turn start including
     * movement point restoration and any turn-specific validations.
     *
     * @param Player $player Player to start turn for
     * @return void
     */
    public function startNewTurn(Player $player): void
    {
        $player->startNewTurn();
    }

    /**
     * Validates if player can start a new turn
     *
     * @param Player $player Player to validate
     * @return bool True if player can start new turn
     */
    public function canStartNewTurn(Player $player): bool
    {
        // Domain rule: Player can always start new turn
        // Future: Could add cooldowns, energy requirements, etc.
        return true;
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
     * Determines if turn should end based on domain rules
     *
     * @param Player $player Player to check
     * @return bool True if turn should end
     */
    public function shouldEndTurn(Player $player): bool
    {
        return !$this->canPlayerContinueTurn($player);
    }

    /**
     * Gets remaining movement for the turn
     *
     * @param Player $player Player to check
     * @return int Number of movement points remaining
     */
    public function getRemainingMovement(Player $player): int
    {
        return $player->getMovementPoints();
    }

    /**
     * Calculates movement efficiency for this turn
     *
     * @param Player $player Player to analyze
     * @return float Movement efficiency as percentage (0.0 to 1.0)
     */
    public function calculateMovementEfficiency(Player $player): float
    {
        $maxPoints = $player->getMaxMovementPoints();
        if ($maxPoints === 0) {
            return 1.0;
        }

        $usedPoints = $maxPoints - $player->getMovementPoints();
        return $usedPoints / $maxPoints;
    }
}
