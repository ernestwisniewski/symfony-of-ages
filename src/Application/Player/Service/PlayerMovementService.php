<?php

namespace App\Application\Player\Service;

use App\Domain\Game\Service\MovementDomainService;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\ValueObject\Position;

/**
 * PlayerMovementService handles player movement validation and execution
 *
 * Application service that orchestrates movement operations by delegating
 * domain logic to the MovementDomainService and coordinating with
 * position validation. Follows Single Responsibility Principle.
 */
class PlayerMovementService
{
    public function __construct(
        private readonly PlayerPositionService $positionService,
        private readonly MovementDomainService $movementDomainService
    )
    {
    }

    /**
     * Attempts to move player to target position
     *
     * @param Player $player Player to move
     * @param Position $targetPosition Target position
     * @param array $mapData Map terrain data
     * @return array Movement result with success status and message
     */
    public function movePlayer(Player $player, Position $targetPosition, array $mapData): array
    {
        // Validate target position is within map bounds
        if (!$this->positionService->isValidMapPosition($targetPosition, count($mapData), count($mapData[0]))) {
            return [
                'success' => false,
                'message' => 'Target position is outside map bounds'
            ];
        }

        // Get terrain at target position
        $terrain = $mapData[$targetPosition->getRow()][$targetPosition->getCol()];

        // Use domain service to validate movement
        $validationResult = $this->movementDomainService->validateMovement(
            $player->getPosition(),
            $targetPosition,
            $terrain
        );

        if (!$validationResult->isValid()) {
            return [
                'success' => false,
                'message' => $validationResult->getReason(),
                'code' => $validationResult->getCode()
            ];
        }

        $movementCost = $validationResult->getMovementCost();

        // Check if player has enough movement points
        if (!$player->canMoveTo($movementCost)) {
            return [
                'success' => false,
                'message' => "Not enough movement points (need: {$movementCost}, have: {$player->getMovementPoints()})"
            ];
        }

        // Attempt movement using domain logic
        $success = $player->moveTo($targetPosition, $movementCost);

        if ($success) {
            return [
                'success' => true,
                'message' => "Moved to {$terrain['name']} (cost: {$movementCost})",
                'remainingMovement' => $player->getMovementPoints(),
                'events' => $player->getDomainEvents() // Include domain events for potential handling
            ];
        } else {
            return [
                'success' => false,
                'message' => "Movement failed unexpectedly"
            ];
        }
    }

    /**
     * Checks if player can move to a position with specified movement cost
     *
     * @param Player $player Player to check
     * @param int $movementCost Cost of the movement
     * @return bool True if player can move
     */
    public function canPlayerMoveToPosition(Player $player, int $movementCost): bool
    {
        return $player->canMoveTo($movementCost);
    }

    /**
     * Validates if two positions are adjacent (for hex grid movement)
     *
     * @param Position $from Starting position
     * @param Position $to Target position
     * @return bool True if positions are adjacent
     */
    public function arePositionsAdjacent(Position $from, Position $to): bool
    {
        return $this->movementDomainService->arePositionsAdjacent($from, $to);
    }

    /**
     * Gets movement cost for specific terrain using domain service
     *
     * @param array $terrainData Terrain data from map
     * @return int Movement cost (0 = impassable)
     */
    public function getTerrainMovementCost(array $terrainData): int
    {
        return $this->movementDomainService->calculateMovementCost($terrainData);
    }
}
