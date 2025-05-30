<?php

namespace App\Domain\Shared\ValueObject;

/**
 * MapConfiguration holds shared map constants
 *
 * Centralized configuration for map dimensions and display properties
 * using modern PHP 8.4 features and enum-like constant pattern.
 */
final class MapConfiguration
{
    public const int ROWS = 100;
    public const int COLS = 100;
    public const int HEX_SIZE = 58;

    private static ?self $instance = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        return self::$instance ??= new self;
    }

    /**
     * Gets map configuration as array
     */
    public static function getConfig(array $additionalConfig = []): array
    {
        return array_merge([
            'rows' => self::ROWS,
            'cols' => self::COLS,
            'size' => self::HEX_SIZE,
            'totalTiles' => self::getTotalTiles()
        ], $additionalConfig);
    }

    /**
     * Gets total number of tiles on the map
     */
    public static function getTotalTiles(): int
    {
        return self::ROWS * self::COLS;
    }

    /**
     * Validates if coordinates are within map bounds
     */
    public static function areCoordinatesValid(int $row, int $col): bool
    {
        return $row >= 0 && $row < self::ROWS && $col >= 0 && $col < self::COLS;
    }
}
