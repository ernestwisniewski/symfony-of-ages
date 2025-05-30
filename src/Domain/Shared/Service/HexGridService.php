<?php

namespace App\Domain\Shared\Service;

use App\Domain\Player\ValueObject\Position;

/**
 * HexGridService provides centralized hexagonal grid operations
 *
 * Domain service that handles all hexagonal grid calculations,
 * neighbor finding, and distance calculations to eliminate code duplication
 * across different parts of the application.
 */
class HexGridService
{
    /**
     * Gets adjacent positions in hexagonal grid
     *
     * @param Position $position Current position
     * @param int $mapRows Number of map rows
     * @param int $mapCols Number of map columns
     * @return Position[] Array of adjacent positions
     */
    public function getAdjacentPositions(Position $position, int $mapRows, int $mapCols): array
    {
        $row = $position->getRow();
        $col = $position->getCol();
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
     * Gets neighboring tiles for a given position
     *
     * @param array $map Current map state
     * @param Position $position Current position
     * @param int $maxRows Total rows in map
     * @param int $maxCols Total columns in map
     * @return array Array of neighboring tile data
     */
    public function getNeighborTiles(array $map, Position $position, int $maxRows, int $maxCols): array
    {
        $neighbors = [];
        $adjacentPositions = $this->getAdjacentPositions($position, $maxRows, $maxCols);

        foreach ($adjacentPositions as $adjPosition) {
            $neighbors[] = $map[$adjPosition->getRow()][$adjPosition->getCol()];
        }

        return $neighbors;
    }

    /**
     * Counts neighbors of a specific terrain type
     *
     * @param array $map Current map state
     * @param Position $position Current position
     * @param int $maxRows Total rows in map
     * @param int $maxCols Total columns in map
     * @param string $terrainType Terrain type to count
     * @return int Number of neighbors with specified terrain type
     */
    public function countNeighborsOfType(array $map, Position $position, int $maxRows, int $maxCols, string $terrainType): int
    {
        $neighbors = $this->getNeighborTiles($map, $position, $maxRows, $maxCols);
        $count = 0;

        foreach ($neighbors as $neighbor) {
            if ($neighbor['type'] === $terrainType) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Gets terrain type counts for all neighbors
     *
     * @param array $map Current map state
     * @param Position $position Current position
     * @param int $maxRows Total rows in map
     * @param int $maxCols Total columns in map
     * @return array Associative array of terrain_type => count
     */
    public function getNeighborTerrainCounts(array $map, Position $position, int $maxRows, int $maxCols): array
    {
        $neighbors = $this->getNeighborTiles($map, $position, $maxRows, $maxCols);
        $terrainCounts = [];

        foreach ($neighbors as $neighbor) {
            $terrainType = $neighbor['type'];
            $terrainCounts[$terrainType] = ($terrainCounts[$terrainType] ?? 0) + 1;
        }

        return $terrainCounts;
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
        return $this->calculateDistance($from, $to) <= 1;
    }
}
