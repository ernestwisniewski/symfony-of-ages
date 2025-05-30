<?php

namespace App\Application\Player\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Service\PlayerTurnDomainService;
use App\Domain\Player\ValueObject\Position;
use App\Domain\Shared\Service\HexGridService;

/**
 * MovementCalculationService - application service for calculating possible player moves
 *
 * Responsible for business logic related to calculating all available
 * positions where a player can move in the current turn.
 * Takes into account player movement points and movement cost to different terrains.
 * This is an application service as it coordinates between domain services and map data.
 */
class MovementCalculationService
{
    public function __construct(
        private readonly PlayerTurnDomainService $turnDomainService,
        private readonly HexGridService          $hexGridService
    )
    {
    }

    /**
     * Calculates all possible moves for a player
     *
     * @param Player $player Player for whom we calculate moves
     * @param array $mapData Map terrain data
     * @param int $mapRows Number of map rows
     * @param int $mapCols Number of map columns
     * @return array Array of possible moves with additional information
     */
    public function calculatePossibleMoves(Player $player, array $mapData, int $mapRows, int $mapCols): array
    {
        $currentPosition = $player->getPosition();
        $availableMovementPoints = $player->currentMovementPoints;

        if ($availableMovementPoints <= 0) {
            return [];
        }

        $possibleMoves = [];

        // Check all adjacent positions (using centralized hex grid service)
        $adjacentPositions = $this->hexGridService->getAdjacentPositions($currentPosition, $mapRows, $mapCols);

        foreach ($adjacentPositions as $position) {
            $terrainData = $mapData[$position->row][$position->col];

            // Validate movement using centralized domain service
            $validationResult = $this->turnDomainService->validateMovement(
                $currentPosition,
                $position,
                $terrainData
            );

            if ($validationResult->isValid()) {
                $movementCost = $validationResult->getMovementCost();

                // Check if player has sufficient movement points
                if ($availableMovementPoints >= $movementCost) {
                    $possibleMoves[] = [
                        'position' => $position->toArray(),
                        'terrain' => $terrainData,
                        'movementCost' => $movementCost,
                        'remainingMovementAfter' => $availableMovementPoints - $movementCost,
                        'canAfford' => true
                    ];
                }
            }
        }

        return $possibleMoves;
    }

    /**
     * Calculates detailed movement options with grouping by cost
     *
     * @param Player $player Player for whom we calculate moves
     * @param array $mapData Map terrain data
     * @param int $mapRows Number of map rows
     * @param int $mapCols Number of map columns
     * @return array Detailed information about possible moves
     */
    public function calculateDetailedMovementOptions(Player $player, array $mapData, int $mapRows, int $mapCols): array
    {
        $possibleMoves = $this->calculatePossibleMoves($player, $mapData, $mapRows, $mapCols);

        // Group moves by movement cost
        $movesByCost = [];
        $terrainTypeCount = [];

        foreach ($possibleMoves as $move) {
            $cost = $move['movementCost'];
            $terrainType = $move['terrain']['type'];

            if (!isset($movesByCost[$cost])) {
                $movesByCost[$cost] = [];
            }

            $movesByCost[$cost][] = $move;
            $terrainTypeCount[$terrainType] = ($terrainTypeCount[$terrainType] ?? 0) + 1;
        }

        return [
            'totalPossibleMoves' => count($possibleMoves),
            'currentMovementPoints' => $player->currentMovementPoints,
            'maxMovementPoints' => $player->maxMovementPoints,
            'movesByCost' => $movesByCost,
            'availableTerrainTypes' => $terrainTypeCount,
            'allMoves' => $possibleMoves,
            'hasAffordableMoves' => array_any($possibleMoves, fn($move) => $move['canAfford']),
            'bestAffordableMove' => array_find($possibleMoves, fn($move) => $move['canAfford'] && $move['movementCost'] === 1),
            'cheapestMove' => array_find($possibleMoves, fn($move) => $move['canAfford'] && $move['movementCost'] === min(array_column($possibleMoves, 'movementCost')))
        ];
    }

    /**
     * Checks if player can move to specific position
     *
     * @param Player $player Player
     * @param Position $targetPosition Target position
     * @param array $mapData Map data
     * @return array Information about movement possibility
     */
    public function canPlayerMoveTo(Player $player, Position $targetPosition, array $mapData): array
    {
        // Check if position is in range (adjacent)
        if (!$this->turnDomainService->arePositionsAdjacent($player->getPosition(), $targetPosition)) {
            return [
                'canMove' => false,
                'reason' => 'Position is not adjacent',
                'code' => 'NOT_ADJACENT'
            ];
        }

        $terrainData = $mapData[$targetPosition->row][$targetPosition->col];

        // Validate movement
        $validationResult = $this->turnDomainService->validateMovement(
            $player->getPosition(),
            $targetPosition,
            $terrainData
        );

        if (!$validationResult->isValid()) {
            return [
                'canMove' => false,
                'reason' => $validationResult->getReason(),
                'code' => $validationResult->getCode()
            ];
        }

        $movementCost = $validationResult->getMovementCost();
        $canAfford = $player->currentMovementPoints >= $movementCost;

        return [
            'canMove' => $canAfford,
            'movementCost' => $movementCost,
            'remainingMovementAfter' => $canAfford ? $player->currentMovementPoints - $movementCost : 0,
            'reason' => $canAfford ? 'Move is valid' : 'Insufficient movement points',
            'code' => $canAfford ? 'VALID' : 'INSUFFICIENT_MOVEMENT_POINTS'
        ];
    }

    /**
     * Finds optimal move based on specific criteria using PHP 8.4 array functions
     */
    public function findOptimalMove(Player $player, array $mapData, int $mapRows, int $mapCols, string $criteria = 'cheapest'): ?array
    {
        $possibleMoves = $this->calculatePossibleMoves($player, $mapData, $mapRows, $mapCols);

        return match ($criteria) {
            'cheapest' => array_find($possibleMoves, fn($move) => $move['movementCost'] === min(array_column($possibleMoves, 'movementCost'))),
            'expensive' => array_find($possibleMoves, fn($move) => $move['movementCost'] === max(array_column($possibleMoves, 'movementCost'))),
            'plains' => array_find($possibleMoves, fn($move) => $move['terrain']['type'] === 'plains'),
            'forest' => array_find($possibleMoves, fn($move) => $move['terrain']['type'] === 'forest'),
            default => $possibleMoves[0] ?? null
        };
    }

    /**
     * Checks if all moves meet certain criteria using PHP 8.4 array_all
     */
    public function hasOnlyExpensiveMoves(Player $player, array $mapData, int $mapRows, int $mapCols, int $maxCost = 2): bool
    {
        $possibleMoves = $this->calculatePossibleMoves($player, $mapData, $mapRows, $mapCols);

        return array_all($possibleMoves, fn($move) => $move['movementCost'] > $maxCost);
    }
}
