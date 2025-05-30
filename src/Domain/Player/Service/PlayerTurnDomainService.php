<?php

namespace App\Domain\Player\Service;

use App\Domain\Map\Enum\TerrainType;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\ValueObject\Position;
use App\Domain\Shared\Service\HexGridService;

/**
 * PlayerTurnDomainService - centralized domain service for turn and movement management
 *
 * Consolidates all turn-related and movement-related domain logic:
 * - Turn lifecycle (start, end, validation)
 * - Movement point management
 * - Movement validation and execution
 * - Turn state queries and analysis
 *
 * This eliminates duplication between MovementDomainService and PlayerTurnDomainService
 * while providing a single source of truth for turn/movement logic.
 */
class PlayerTurnDomainService
{
    public function __construct(
        private readonly HexGridService $hexGridService
    )
    {
    }

    // =================== TURN LIFECYCLE ===================

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
     * Determines if turn should end based on domain rules
     *
     * @param Player $player Player to check
     * @return bool True if turn should end
     */
    public function shouldEndTurn(Player $player): bool
    {
        return !$this->canPlayerContinueTurn($player);
    }

    // =================== MOVEMENT MANAGEMENT ===================

    /**
     * Checks if player can continue their turn (has movement points)
     *
     * @param Player $player Player to check
     * @return bool True if player can still move
     */
    public function canPlayerContinueTurn(Player $player): bool
    {
        return $player->getMovementPoints()->hasPointsRemaining();
    }

    /**
     * Checks if player can afford a specific movement cost
     *
     * @param Player $player Player to check
     * @param int $movementCost Movement cost to check
     * @return bool True if player can afford the movement
     */
    public function canAffordMovement(Player $player, int $movementCost): bool
    {
        return $player->getMovementPoints()->canSpend($movementCost);
    }

    /**
     * Validates and executes player movement
     *
     * Combines movement validation and execution in a single operation
     * following domain rules for both terrain and movement points.
     *
     * @param Player $player Player to move
     * @param Position $targetPosition Target position to move to
     * @param array $terrainData Terrain data for the target position
     * @return MovementExecutionResult Result of the movement execution
     */
    public function executeMovement(Player $player, Position $targetPosition, array $terrainData): MovementExecutionResult
    {
        // Validate movement constraints
        $validationResult = $this->validateMovement($player->position, $targetPosition, $terrainData);

        if (!$validationResult->isValid()) {
            return MovementExecutionResult::failed($validationResult->getReason(), $validationResult->getCode());
        }

        $movementCost = $validationResult->getMovementCost();

        // Check if player can afford the movement
        if (!$this->canAffordMovement($player, $movementCost)) {
            return MovementExecutionResult::failed(
                "Insufficient movement points. Required: {$movementCost}, Available: {$player->currentMovementPoints}",
                'INSUFFICIENT_MOVEMENT_POINTS'
            );
        }

        // Execute the movement
        $success = $player->moveTo($targetPosition, $movementCost);

        if (!$success) {
            return MovementExecutionResult::failed('Movement execution failed', 'EXECUTION_FAILED');
        }

        return MovementExecutionResult::success($movementCost, $player->currentMovementPoints);
    }

    /**
     * Validates movement between positions according to domain rules
     *
     * @param Position $from Starting position
     * @param Position $to Target position
     * @param array $terrainData Terrain data for the target position
     * @return MovementValidationResult Result of the movement validation
     */
    public function validateMovement(Position $from, Position $to, array $terrainData): MovementValidationResult
    {
        $terrainType = TerrainType::from($terrainData['type']);

        // Check if target terrain is passable
        if (!$terrainType->getProperties()->isPassable) {
            return MovementValidationResult::invalid(
                "Cannot move to impassable terrain: {$terrainType->getProperties()->name}",
                'IMPASSABLE_TERRAIN'
            );
        }

        // Check if positions are adjacent
        if (!$this->arePositionsAdjacent($from, $to)) {
            return MovementValidationResult::invalid(
                'Target position is not adjacent to current position',
                'NOT_ADJACENT'
            );
        }

        // Get movement cost
        $movementCost = $this->calculateMovementCost($terrainData);

        return MovementValidationResult::valid($movementCost);
    }

    /**
     * Calculates movement cost for terrain
     *
     * @param array $terrainData Terrain data for the target position
     * @return int Movement cost for the terrain
     */
    public function calculateMovementCost(array $terrainData): int
    {
        $terrainType = TerrainType::from($terrainData['type']);
        return $terrainType->getProperties()->movementCost;
    }

    /**
     * Checks if two positions are adjacent in hex grid
     *
     * @param Position $from Starting position
     * @param Position $to Target position
     * @return bool True if positions are adjacent
     */
    public function arePositionsAdjacent(Position $from, Position $to): bool
    {
        return $this->hexGridService->arePositionsAdjacent($from, $to);
    }

    // =================== TURN STATE QUERIES ===================

    /**
     * Gets remaining movement for the turn
     *
     * @param Player $player Player to check
     * @return int Number of movement points remaining
     */
    public function getRemainingMovementPoints(Player $player): int
    {
        return $player->currentMovementPoints;
    }

    /**
     * Gets maximum movement points for the player
     *
     * @param Player $player Player to check
     * @return int Maximum number of movement points for the player
     */
    public function getMaximumMovementPoints(Player $player): int
    {
        return $player->maxMovementPoints;
    }

    /**
     * Calculates movement efficiency for this turn
     *
     * @param Player $player Player to analyze
     * @return float Movement efficiency as percentage (0.0 to 1.0)
     */
    public function calculateMovementEfficiency(Player $player): float
    {
        $maxPoints = $player->maxMovementPoints;
        if ($maxPoints === 0) {
            return 1.0;
        }

        $usedPoints = $maxPoints - $player->currentMovementPoints;
        return $usedPoints / $maxPoints;
    }

    /**
     * Gets comprehensive turn state analysis
     *
     * @param Player $player Player to analyze
     * @return array Comprehensive turn state analysis
     */
    public function getTurnState(Player $player): array
    {
        return [
            'canContinueTurn' => $this->canPlayerContinueTurn($player),
            'shouldEndTurn' => $this->shouldEndTurn($player),
            'remainingMovementPoints' => $this->getRemainingMovementPoints($player),
            'maximumMovementPoints' => $this->getMaximumMovementPoints($player),
            'movementEfficiency' => $this->calculateMovementEfficiency($player),
            'isMovementExhausted' => !$this->canPlayerContinueTurn($player),
        ];
    }
}
