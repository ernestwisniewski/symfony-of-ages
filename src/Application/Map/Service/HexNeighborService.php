<?php

namespace App\Application\Map\Service;

use App\Domain\Player\ValueObject\Position;
use App\Domain\Shared\Service\HexGridService;

/**
 * HexNeighborService handles hexagonal grid neighbor calculations for maps
 *
 * Application service that delegates to the domain HexGridService
 * while providing map-specific convenience methods.
 */
class HexNeighborService
{
    public function __construct(
        private readonly HexGridService $hexGridService
    ) {
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
        return $this->hexGridService->getNeighborTiles($map, $position, $maxRows, $maxCols);
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

        return array_map(fn (Position $pos) => ['row' => $pos->getRow(), 'col' => $pos->getCol()], $adjacentPositions);
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
        return $this->hexGridService->countNeighborsOfType($map, $position, $maxRows, $maxCols, $terrainType);
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
        return $this->hexGridService->getNeighborTerrainCounts($map, $position, $maxRows, $maxCols);
    }
}
