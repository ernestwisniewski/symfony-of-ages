<?php

namespace App\Domain\Shared\ValueObject;

/**
 * MapConfiguration holds shared map constants
 *
 * Centralized configuration for map dimensions and display properties
 * to avoid duplication across controllers and services.
 */
final class MapConfiguration
{
    /** @var int Number of columns in the hex grid */
    public const int COLS = 100;

    /** @var int Number of rows in the hex grid */
    public const int ROWS = 100;

    /** @var int Size (radius) of individual hexagons in pixels */
    public const int HEX_SIZE = 58;

    /**
     * Gets map configuration as array
     *
     * @param array $additionalConfig Additional configuration parameters
     * @return array Complete map configuration
     */
    public static function getConfig(array $additionalConfig = []): array
    {
        return array_merge([
            'rows' => self::ROWS,
            'cols' => self::COLS,
            'size' => self::HEX_SIZE
        ], $additionalConfig);
    }

    /**
     * Gets total number of tiles in the map
     *
     * @return int Total tiles
     */
    public static function getTotalTiles(): int
    {
        return self::ROWS * self::COLS;
    }

    /**
     * Validates if coordinates are within map bounds
     *
     * @param int $row Row coordinate
     * @param int $col Column coordinate
     * @return bool True if within bounds
     */
    public static function areCoordinatesValid(int $row, int $col): bool
    {
        return $row >= 0 && $row < self::ROWS && $col >= 0 && $col < self::COLS;
    }
}
