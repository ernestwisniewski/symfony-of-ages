<?php

namespace App\Application\Player\Service;

use App\Domain\Player\Enum\TerrainType;
use App\Domain\Player\ValueObject\Position;

/**
 * PlayerPositionService handles player position generation and validation
 *
 * Responsible for generating valid starting positions for players and
 * validating position suitability. Follows Single Responsibility Principle
 * by focusing only on position-related logic.
 */
class PlayerPositionService
{
    /** @var int Maximum attempts to find a valid starting position */
    private const int MAX_POSITION_ATTEMPTS = 200;

    /** @var float Start of safe area (percentage of map) */
    private const float SAFE_AREA_START = 0.35;

    /** @var float End of safe area (percentage of map) */
    private const float SAFE_AREA_END = 0.65;

    /** @var int Maximum radius for fallback position search */
    private const int MAX_FALLBACK_RADIUS = 10;

    /**
     * Generates a valid starting position avoiding water and other obstacles
     *
     * @param int $mapRows Number of rows in the map
     * @param int $mapCols Number of columns in the map
     * @param array $mapData Map terrain data for position validation
     * @return Position Valid starting position
     */
    public function generateValidStartingPosition(int $mapRows, int $mapCols, array $mapData): Position
    {
        $safeAreaBounds = $this->calculateSafeAreaBounds($mapRows, $mapCols);

        error_log("Attempting to generate player position in safe area: rows({$safeAreaBounds['minRow']}-{$safeAreaBounds['maxRow']}), cols({$safeAreaBounds['minCol']}-{$safeAreaBounds['maxCol']})");

        // Try to find position in safe area first
        $position = $this->searchPositionInSafeArea($safeAreaBounds, $mapData);

        if ($position) {
            return $position;
        }

        error_log("Could not find valid position in safe area after " . self::MAX_POSITION_ATTEMPTS . " attempts, trying fallback positions");

        // Try fallback strategies
        return $this->findFallbackPosition($mapRows, $mapCols, $mapData);
    }

    /**
     * Validates if position is suitable for player starting location
     *
     * @param Position $position Position to validate
     * @param array $mapData Map terrain data
     * @return bool True if position is valid for starting
     */
    public function isValidStartingPosition(Position $position, array $mapData): bool
    {
        $terrain = $mapData[$position->getRow()][$position->getCol()];
        $terrainType = TerrainType::from($terrain['type']);

        // Don't start on water (impassable)
        return $terrainType->getProperties()['movementCost'] > 0;
    }

    /**
     * Validates if position is within map bounds
     *
     * @param Position $position Position to validate
     * @param int $mapRows Number of rows in the map
     * @param int $mapCols Number of columns in the map
     * @return bool True if position is within bounds
     */
    public function isValidMapPosition(Position $position, int $mapRows, int $mapCols): bool
    {
        return $position->isValidForMap($mapRows, $mapCols);
    }

    // Private helper methods

    /**
     * Calculates safe area bounds for position generation
     */
    private function calculateSafeAreaBounds(int $mapRows, int $mapCols): array
    {
        return [
            'minRow' => intval($mapRows * self::SAFE_AREA_START),
            'maxRow' => intval($mapRows * self::SAFE_AREA_END),
            'minCol' => intval($mapCols * self::SAFE_AREA_START),
            'maxCol' => intval($mapCols * self::SAFE_AREA_END)
        ];
    }

    /**
     * Searches for a valid position within the safe area
     */
    private function searchPositionInSafeArea(array $bounds, array $mapData): ?Position
    {
        for ($attempts = 0; $attempts < self::MAX_POSITION_ATTEMPTS; $attempts++) {
            $row = mt_rand($bounds['minRow'], $bounds['maxRow']);
            $col = mt_rand($bounds['minCol'], $bounds['maxCol']);
            $position = new Position($row, $col);

            if ($this->isValidStartingPosition($position, $mapData)) {
                error_log("Generated valid starting position: ({$row}, {$col}) in safe area");
                return $position;
            } else {
                $terrain = $mapData[$row][$col] ?? null;
                $terrainName = $terrain['name'] ?? 'unknown';
                error_log("Position ({$row}, {$col}) rejected: terrain = {$terrainName}");
            }
        }

        return null;
    }

    /**
     * Finds fallback position when safe area search fails
     */
    private function findFallbackPosition(int $mapRows, int $mapCols, array $mapData): Position
    {
        $centerRow = intval($mapRows / 2);
        $centerCol = intval($mapCols / 2);

        // Try center first
        $centerPosition = new Position($centerRow, $centerCol);
        if ($this->isValidStartingPosition($centerPosition, $mapData)) {
            error_log("Using center position: ({$centerRow}, {$centerCol})");
            return $centerPosition;
        }

        // Search in expanding radius around center
        $position = $this->searchAroundCenter($centerRow, $centerCol, $mapRows, $mapCols, $mapData);

        if ($position) {
            return $position;
        }

        // Final fallback: Force to center (should not happen with proper map generation)
        error_log("WARNING: Using center position ({$centerRow}, {$centerCol}) even though it may be water");
        return new Position($centerRow, $centerCol);
    }

    /**
     * Searches for valid position in expanding radius around center
     */
    private function searchAroundCenter(int $centerRow, int $centerCol, int $mapRows, int $mapCols, array $mapData): ?Position
    {
        for ($radius = 1; $radius <= self::MAX_FALLBACK_RADIUS; $radius++) {
            $position = $this->searchInRadius($centerRow, $centerCol, $radius, $mapRows, $mapCols, $mapData);

            if ($position) {
                error_log("Using fallback position: ({$position->getRow()}, {$position->getCol()}) at radius {$radius} from center");
                return $position;
            }
        }

        return null;
    }

    /**
     * Searches for valid position at specific radius from center
     */
    private function searchInRadius(int $centerRow, int $centerCol, int $radius, int $mapRows, int $mapCols, array $mapData): ?Position
    {
        for ($dr = -$radius; $dr <= $radius; $dr++) {
            for ($dc = -$radius; $dc <= $radius; $dc++) {
                // Only check border of current radius
                if (abs($dr) + abs($dc) !== $radius) {
                    continue;
                }

                $fallbackRow = $centerRow + $dr;
                $fallbackCol = $centerCol + $dc;

                if (!$this->isWithinBounds($fallbackRow, $fallbackCol, $mapRows, $mapCols)) {
                    continue;
                }

                $fallbackPosition = new Position($fallbackRow, $fallbackCol);
                if ($this->isValidStartingPosition($fallbackPosition, $mapData)) {
                    return $fallbackPosition;
                }
            }
        }

        return null;
    }

    /**
     * Checks if coordinates are within map bounds
     */
    private function isWithinBounds(int $row, int $col, int $mapRows, int $mapCols): bool
    {
        return $row >= 0 && $row < $mapRows && $col >= 0 && $col < $mapCols;
    }
}
