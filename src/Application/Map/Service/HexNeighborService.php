<?php

namespace App\Application\Map\Service;

use App\Domain\Player\ValueObject\Position;
use App\Domain\Shared\Service\HexGridService;

/**
 * HexNeighborService handles hexagonal grid operations for map structures
 *
 * Application service that combines pure hexagonal grid domain logic
 * with map data structure handling. Delegates positional calculations
 * to HexGridService and handles map-specific operations.
 */
class HexNeighborService
{
    public function __construct(
        private readonly HexGridService $hexGridService
    )
    {
    }

    /**
     * Gets neighboring tiles for a given position using hexagonal grid logic
     *
     * @param array $map Current map state
     * @param int $row Current row
     * @param int $col Current column
     * @param int $maxRows Total rows in map
     * @param int $maxCols Total columns in map
     * @return array Array of neighboring tile data
     */
    public function getNeighbors(array $map, int $row, int $col, int $maxRows, int $maxCols): array
    {
        $position = new Position($row, $col);
        return $this->getNeighborTiles($map, $position, $maxRows, $maxCols);
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
        $adjacentPositions = $this->hexGridService->getAdjacentPositions($position, $maxRows, $maxCols);

        foreach ($adjacentPositions as $adjPosition) {
            $neighbors[] = $map[$adjPosition->row][$adjPosition->col];
        }

        return $neighbors;
    }

    /**
     * Gets direction vectors for hexagonal neighbors
     *
     * @param int $row Current row (needed for odd/even row offset)
     * @return array Array of [row_offset, col_offset] direction vectors
     */
    public function getHexDirections(int $row): array
    {
        return $this->hexGridService->getHexDirections($row);
    }

    /**
     * Gets neighbor positions (coordinates only) for a given position
     *
     * @param int $row Current row
     * @param int $col Current column
     * @param int $maxRows Total rows in map
     * @param int $maxCols Total columns in map
     * @return array Array of [row, col] coordinate pairs
     */
    public function getNeighborPositions(int $row, int $col, int $maxRows, int $maxCols): array
    {
        $position = new Position($row, $col);
        $adjacentPositions = $this->hexGridService->getAdjacentPositions($position, $maxRows, $maxCols);

        return array_map(fn(Position $pos) => ['row' => $pos->row, 'col' => $pos->col], $adjacentPositions);
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
     * @return int Number of neighbors with specified terrain type
     */
    public function countNeighborsOfType(array $map, int $row, int $col, int $maxRows, int $maxCols, string $terrainType): int
    {
        $position = new Position($row, $col);
        return $this->countNeighborsOfTypeAtPosition($map, $position, $maxRows, $maxCols, $terrainType);
    }

    /**
     * Counts neighbors of a specific terrain type at position
     *
     * @param array $map Current map state
     * @param Position $position Current position
     * @param int $maxRows Total rows in map
     * @param int $maxCols Total columns in map
     * @param string $terrainType Terrain type to count
     * @return int Number of neighbors with specified terrain type
     */
    public function countNeighborsOfTypeAtPosition(array $map, Position $position, int $maxRows, int $maxCols, string $terrainType): int
    {
        $neighbors = $this->getNeighborTiles($map, $position, $maxRows, $maxCols);

        return count(array_filter($neighbors, fn($neighbor) => $neighbor['type'] === $terrainType));
    }

    /**
     * Gets terrain type counts for all neighbors
     *
     * @param array $map Current map state
     * @param int $row Current row
     * @param int $col Current column
     * @param int $maxRows Total rows in map
     * @param int $maxCols Total columns in map
     * @return array Associative array of terrain_type => count
     */
    public function getNeighborTerrainCounts(array $map, int $row, int $col, int $maxRows, int $maxCols): array
    {
        $position = new Position($row, $col);
        return $this->getNeighborTerrainCountsAtPosition($map, $position, $maxRows, $maxCols);
    }

    /**
     * Gets terrain type counts for all neighbors at position
     *
     * @param array $map Current map state
     * @param Position $position Current position
     * @param int $maxRows Total rows in map
     * @param int $maxCols Total columns in map
     * @return array Associative array of terrain_type => count
     */
    public function getNeighborTerrainCountsAtPosition(array $map, Position $position, int $maxRows, int $maxCols): array
    {
        $neighbors = $this->getNeighborTiles($map, $position, $maxRows, $maxCols);

        // Extract terrain types using array_map then count using array_count_values
        $terrainTypes = array_map(fn($neighbor) => $neighbor['type'], $neighbors);

        return array_count_values($terrainTypes);
    }

    /**
     * Checks if position has any neighbors of specific terrain type using PHP 8.4 array_any
     *
     * @param array $map Current map state
     * @param Position $position Position to check
     * @param int $maxRows Total rows in map
     * @param int $maxCols Total columns in map
     * @param string $terrainType Terrain type to search for
     * @return bool True if any neighbor has the specified terrain type
     */
    public function hasAnyNeighborsOfType(array $map, Position $position, int $maxRows, int $maxCols, string $terrainType): bool
    {
        $neighbors = $this->getNeighborTiles($map, $position, $maxRows, $maxCols);

        return array_any($neighbors, fn($neighbor) => $neighbor['type'] === $terrainType);
    }

    /**
     * Checks if all neighbors are of specific terrain type using PHP 8.4 array_all
     *
     * @param array $map Current map state
     * @param Position $position Position to check
     * @param int $maxRows Total rows in map
     * @param int $maxCols Total columns in map
     * @param string $terrainType Terrain type to check
     * @return bool True if all neighbors have the specified terrain type
     */
    public function areAllNeighborsOfType(array $map, Position $position, int $maxRows, int $maxCols, string $terrainType): bool
    {
        $neighbors = $this->getNeighborTiles($map, $position, $maxRows, $maxCols);

        if (empty($neighbors)) {
            return false;
        }

        return array_all($neighbors, fn($neighbor) => $neighbor['type'] === $terrainType);
    }
}
