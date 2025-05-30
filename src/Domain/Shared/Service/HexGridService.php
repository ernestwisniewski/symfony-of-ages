<?php

namespace App\Domain\Shared\Service;

use App\Domain\Player\ValueObject\Position;

/**
 * HexGridService provides pure hexagonal grid domain logic
 *
 * Clean domain service focused solely on hexagonal grid calculations,
 * positions, directions, and geometry. Does NOT handle map data structures.
 * Map structure handling is delegated to Application layer services.
 */
class HexGridService
{
    /**
     * Gets adjacent positions in hexagonal grid
     *
     * @param Position $position Current position
     * @param int $mapRows Number of map rows for boundary validation
     * @param int $mapCols Number of map columns for boundary validation
     * @return Position[] Array of adjacent positions
     */
    public function getAdjacentPositions(Position $position, int $mapRows, int $mapCols): array
    {
        $row = $position->row;
        $col = $position->col;
        $adjacentPositions = [];

        $directions = $this->getHexDirections($row);

        foreach ($directions as $direction) {
            $newRow = $row + $direction[0];
            $newCol = $col + $direction[1];

            if ($this->isWithinBounds($newRow, $newCol, $mapRows, $mapCols)) {
                $adjacentPositions[] = new Position($newRow, $newCol);
            }
        }

        return $adjacentPositions;
    }

    /**
     * Gets direction vectors for hexagonal neighbors
     *
     * @param int $row Current row (needed for odd/even row offset)
     * @return array Array of [row_offset, col_offset] direction vectors
     */
    public function getHexDirections(int $row): array
    {
        // Hexagonal grid directions depend on whether row is odd or even
        if ($row % 2 === 0) {
            // Even row
            return [
                [-1, -1], [-1, 0],  // Top-left, Top-right
                [0, -1], [0, 1],    // Left, Right
                [1, -1], [1, 0]     // Bottom-left, Bottom-right
            ];
        } else {
            // Odd row
            return [
                [-1, 0], [-1, 1],   // Top-left, Top-right
                [0, -1], [0, 1],    // Left, Right
                [1, 0], [1, 1]      // Bottom-left, Bottom-right
            ];
        }
    }

    /**
     * Checks if coordinates are within map bounds
     *
     * @param int $row Row coordinate
     * @param int $col Column coordinate
     * @param int $mapRows Total map rows
     * @param int $mapCols Total map columns
     * @return bool True if within bounds
     */
    public function isWithinBounds(int $row, int $col, int $mapRows, int $mapCols): bool
    {
        return $row >= 0 && $row < $mapRows && $col >= 0 && $col < $mapCols;
    }

    /**
     * Calculates hex distance between two positions
     *
     * @param Position $from Starting position
     * @param Position $to Target position
     * @return int Distance in hex steps
     */
    public function calculateDistance(Position $from, Position $to): int
    {
        return $from->distanceTo($to);
    }

    /**
     * Checks if two positions are adjacent
     *
     * @param Position $from Starting position
     * @param Position $to Target position
     * @return bool True if positions are adjacent
     */
    public function arePositionsAdjacent(Position $from, Position $to): bool
    {
        if ($from->equals($to)) {
            return true; // Same position is considered adjacent
        }

        $adjacentPositions = $this->getAdjacentPositions($from, PHP_INT_MAX, PHP_INT_MAX);

        return array_any($adjacentPositions, fn($adjacentPosition) => $adjacentPosition->equals($to));
    }
}
