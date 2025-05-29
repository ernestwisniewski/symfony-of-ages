<?php

namespace App\Application\Map\Service;

/**
 * HexNeighborService handles hexagonal grid neighbor calculations
 *
 * Responsible for calculating neighboring positions in a hexagonal grid system.
 * Follows Single Responsibility Principle by focusing only on hex grid geometry
 * and neighbor relationship calculations.
 */
class HexNeighborService
{
    /**
     * Gets neighboring tiles for a given position using hexagonal grid logic
     *
     * @param array $map Current map state
     * @param int $row Current row
     * @param int $col Current column
     * @param int $maxRows Total rows in map
     * @param int $maxCols Total columns in map
     *
     * @return array Array of neighboring tile data
     */
    public function getNeighbors(array $map, int $row, int $col, int $maxRows, int $maxCols): array
    {
        $neighbors = [];

        // Hexagonal neighbors (6 directions)
        $directions = $this->getHexDirections($row);

        foreach ($directions as $direction) {
            $newRow = $row + $direction[0];
            $newCol = $col + $direction[1];

            // Check bounds
            if ($newRow >= 0 && $newRow < $maxRows && $newCol >= 0 && $newCol < $maxCols) {
                $neighbors[] = $map[$newRow][$newCol];
            }
        }

        return $neighbors;
    }

    /**
     * Gets direction vectors for hexagonal neighbors
     *
     * @param int $row Current row (needed for odd/even row offset)
     *
     * @return array Array of [row_offset, col_offset] direction vectors
     */
    public function getHexDirections(int $row): array
    {
        // Hexagonal grid directions depend on whether row is odd or even
        if ($row % 2 === 0) {
            // Even row
            return [
                [-1, -1], [-1, 0],  // Top-left, Top-right
                [0, -1], [0, 1],   // Left, Right
                [1, -1], [1, 0]    // Bottom-left, Bottom-right
            ];
        } else {
            // Odd row
            return [
                [-1, 0], [-1, 1],  // Top-left, Top-right
                [0, -1], [0, 1],   // Left, Right
                [1, 0], [1, 1]    // Bottom-left, Bottom-right
            ];
        }
    }

    /**
     * Gets neighbor positions (coordinates only) for a given position
     *
     * @param int $row Current row
     * @param int $col Current column
     * @param int $maxRows Total rows in map
     * @param int $maxCols Total columns in map
     *
     * @return array Array of [row, col] coordinate pairs
     */
    public function getNeighborPositions(int $row, int $col, int $maxRows, int $maxCols): array
    {
        $positions = [];
        $directions = $this->getHexDirections($row);

        foreach ($directions as $direction) {
            $newRow = $row + $direction[0];
            $newCol = $col + $direction[1];

            // Check bounds
            if ($newRow >= 0 && $newRow < $maxRows && $newCol >= 0 && $newCol < $maxCols) {
                $positions[] = ['row' => $newRow, 'col' => $newCol];
            }
        }

        return $positions;
    }

    /**
     * Counts neighbors of a specific terrain type
     *
     * @param array $map Current map state
     * @param int $row Current row
     * @param int $col Current column
     * @param int $maxRows Total rows in map
     * @param int $maxCols Total columns in map
     * @param string $terrainType Terrain type to count
     *
     * @return int Number of neighbors with specified terrain type
     */
    public function countNeighborsOfType(array $map, int $row, int $col, int $maxRows, int $maxCols, string $terrainType): int
    {
        $neighbors = $this->getNeighbors($map, $row, $col, $maxRows, $maxCols);
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
     * @param int $row Current row
     * @param int $col Current column
     * @param int $maxRows Total rows in map
     * @param int $maxCols Total columns in map
     *
     * @return array Associative array of terrain_type => count
     */
    public function getNeighborTerrainCounts(array $map, int $row, int $col, int $maxRows, int $maxCols): array
    {
        $neighbors = $this->getNeighbors($map, $row, $col, $maxRows, $maxCols);
        $terrainCounts = [];

        foreach ($neighbors as $neighbor) {
            $terrainType = $neighbor['type'];
            $terrainCounts[$terrainType] = ($terrainCounts[$terrainType] ?? 0) + 1;
        }

        return $terrainCounts;
    }
}
