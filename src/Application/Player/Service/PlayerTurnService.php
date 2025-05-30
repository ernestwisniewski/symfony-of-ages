<?php

namespace App\Application\Player\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Service\PlayerTurnDomainService;

/**
 * PlayerTurnService handles turn management coordination
 *
 * Application service that coordinates turn operations and delegates
 * domain logic to PlayerTurnDomainService. Handles orchestration
 * and cross-cutting concerns like logging.
 */
class PlayerTurnService
{
    public function __construct(
        private readonly PlayerTurnDomainService $turnDomainService
    ) {
    }

    /**
     * Starts a new turn for the player
     *
     * @param Player $player Player to start turn for
     * @return void
     */
    public function startPlayerTurn(Player $player): void
    {
        $this->turnDomainService->startNewTurn($player);
    }

    /**
     * Ends the current turn for the player
     *
     * @param Player $player Player to end turn for
     * @return void
     */
    public function endPlayerTurn(Player $player): void
    {
        // Future: Add end-of-turn orchestration like saving state, notifications, etc.
    }

    /**
     * Checks if player has any movement points remaining
     *
     * @param Player $player Player to check
     * @return bool True if player can still move
     */
    public function canPlayerContinueTurn(Player $player): bool
    {
        return $this->turnDomainService->canPlayerContinueTurn($player);
    }

    /**
     * Gets remaining movement points for the player
     *
     * @param Player $player Player to check
     * @return int Number of movement points remaining
     */
    public function getRemainingMovementPoints(Player $player): int
    {
        return $this->turnDomainService->getRemainingMovement($player);
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
     * Checks if player turn is effectively over
     *
     * @param Player $player Player to check
     * @return bool True if turn should end
     */
    public function shouldEndTurn(Player $player): bool
    {
        return $this->turnDomainService->shouldEndTurn($player);
    }
}
