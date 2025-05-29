<?php

namespace App\Application\Player\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\ValueObject\Position;
use App\Domain\Player\Enum\TerrainType;

/**
 * PlayerService handles player-related business logic and operations
 *
 * Manages player creation, movement validation, turn management,
 * and interaction with the game map. Serves as an application service
 * coordinating between domain entities and external systems.
 */
class PlayerService
{
    /**
     * Creates a new player with random starting position
     *
     * @param string $name Player name
     * @param int $mapRows Number of rows in the map
     * @param int $mapCols Number of columns in the map
     * @param array $mapData Map terrain data for position validation
     * @return Player New player instance
     */
    public function createPlayer(string $name, int $mapRows, int $mapCols, array $mapData): Player
    {
        $position = $this->generateValidStartingPosition($mapRows, $mapCols, $mapData);
        $playerId = $this->generatePlayerId();
        $playerColor = $this->generatePlayerColor();
        
        return new Player($playerId, $position, $name, 3, $playerColor);
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
        if (!$targetPosition->isValidForMap(count($mapData), count($mapData[0]))) {
            return [
                'success' => false,
                'message' => 'Target position is outside map bounds'
            ];
        }

        // Get terrain at target position
        $terrain = $mapData[$targetPosition->getRow()][$targetPosition->getCol()];
        $terrainType = TerrainType::from($terrain['type']);
        $movementCost = $terrainType->getProperties()['movementCost'];

        // Check if terrain is passable
        if ($movementCost === 0) {
            return [
                'success' => false,
                'message' => 'Cannot move to impassable terrain (water)'
            ];
        }

        // Validate distance (can only move to adjacent hexes)
        $distance = $player->getPosition()->distanceTo($targetPosition);
        if ($distance > 1) {
            return [
                'success' => false,
                'message' => 'Can only move to adjacent hexes'
            ];
        }

        // Attempt movement
        $success = $player->moveTo($targetPosition, $movementCost);
        
        if ($success) {
            return [
                'success' => true,
                'message' => "Moved to {$terrain['name']} (cost: {$movementCost})",
                'remainingMovement' => $player->getMovementPoints()
            ];
        } else {
            return [
                'success' => false,
                'message' => "Not enough movement points (need: {$movementCost}, have: {$player->getMovementPoints()})"
            ];
        }
    }

    /**
     * Starts a new turn for the player
     */
    public function startPlayerTurn(Player $player): void
    {
        $player->startNewTurn();
    }

    /**
     * Generates a valid starting position avoiding water and other obstacles
     */
    private function generateValidStartingPosition(int $mapRows, int $mapCols, array $mapData): Position
    {
        $maxAttempts = 100;
        $attempts = 0;

        do {
            $row = mt_rand(0, $mapRows - 1);
            $col = mt_rand(0, $mapCols - 1);
            $position = new Position($row, $col);
            
            // Check if position is valid (not water, within bounds)
            if ($this->isValidStartingPosition($position, $mapData)) {
                return $position;
            }
            
            $attempts++;
        } while ($attempts < $maxAttempts);

        // Fallback to center of map if no valid position found
        return new Position(intval($mapRows / 2), intval($mapCols / 2));
    }

    /**
     * Validates if position is suitable for player starting location
     */
    private function isValidStartingPosition(Position $position, array $mapData): bool
    {
        $terrain = $mapData[$position->getRow()][$position->getCol()];
        $terrainType = TerrainType::from($terrain['type']);
        
        // Don't start on water (impassable)
        return $terrainType->getProperties()['movementCost'] > 0;
    }

    /**
     * Generates unique player ID
     */
    private function generatePlayerId(): string
    {
        return 'player_' . uniqid();
    }

    /**
     * Generates random player color
     */
    private function generatePlayerColor(): int
    {
        $colors = [
            0xFF6B6B, // Red
            0x4ECDC4, // Teal
            0x45B7D1, // Blue
            0x96CEB4, // Green
            0xFECA57, // Yellow
            0xFF9FF3, // Pink
            0x54A0FF, // Light Blue
            0x5F27CD  // Purple
        ];
        
        return $colors[array_rand($colors)];
    }
} 