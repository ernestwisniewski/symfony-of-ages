<?php

namespace App\Application\Game\Service;

use App\Domain\Game\Service\MovementDomainService;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\ValueObject\Position;

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
        private readonly MovementDomainService $movementDomainService
    ) {
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
        $availableMovementPoints = $player->getMovementPoints();
        
        if ($availableMovementPoints <= 0) {
            return [];
        }

        $possibleMoves = [];
        
        // Check all adjacent positions (in hex grid)
        $adjacentPositions = $this->getAdjacentHexPositions($currentPosition, $mapRows, $mapCols);
        
        foreach ($adjacentPositions as $position) {
            $terrainData = $mapData[$position->getRow()][$position->getCol()];
            
            // Validate movement using domain service
            $validationResult = $this->movementDomainService->validateMovement(
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
            'currentMovementPoints' => $player->getMovementPoints(),
            'maxMovementPoints' => $player->getMaxMovementPoints(),
            'movesByCost' => $movesByCost,
            'availableTerrainTypes' => $terrainTypeCount,
            'allMoves' => $possibleMoves
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
        if (!$this->movementDomainService->arePositionsAdjacent($player->getPosition(), $targetPosition)) {
            return [
                'canMove' => false,
                'reason' => 'Position is not adjacent',
                'code' => 'NOT_ADJACENT'
            ];
        }

        $terrainData = $mapData[$targetPosition->getRow()][$targetPosition->getCol()];
        
        // Validate movement
        $validationResult = $this->movementDomainService->validateMovement(
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
        $canAfford = $player->getMovementPoints() >= $movementCost;

        return [
            'canMove' => $canAfford,
            'movementCost' => $movementCost,
            'remainingMovementAfter' => $canAfford ? $player->getMovementPoints() - $movementCost : 0,
            'reason' => $canAfford ? 'Move is valid' : 'Insufficient movement points',
            'code' => $canAfford ? 'VALID' : 'INSUFFICIENT_MOVEMENT_POINTS'
        ];
    }

    /**
     * Gets adjacent positions in hexagonal grid
     *
     * @param Position $position Current position
     * @param int $mapRows Number of map rows
     * @param int $mapCols Number of map columns
     * @return Position[] Array of adjacent positions
     */
    private function getAdjacentHexPositions(Position $position, int $mapRows, int $mapCols): array
    {
        $row = $position->getRow();
        $col = $position->getCol();
        $adjacentPositions = [];

        // Hex grid directions depend on whether row is even or odd
        if ($row % 2 === 0) {
            // Even row
            $directions = [
                [-1, -1], [-1, 0],  // Top-left, Top-right
                [0, -1], [0, 1],    // Left, Right
                [1, -1], [1, 0]     // Bottom-left, Bottom-right
            ];
        } else {
            // Odd row
            $directions = [
                [-1, 0], [-1, 1],   // Top-left, Top-right
                [0, -1], [0, 1],    // Left, Right
                [1, 0], [1, 1]      // Bottom-left, Bottom-right
            ];
        }

        foreach ($directions as $direction) {
            $newRow = $row + $direction[0];
            $newCol = $col + $direction[1];

            // Check map boundaries
            if ($newRow >= 0 && $newRow < $mapRows && $newCol >= 0 && $newCol < $mapCols) {
                $adjacentPositions[] = new Position($newRow, $newCol);
            }
        }

        return $adjacentPositions;
    }
} 