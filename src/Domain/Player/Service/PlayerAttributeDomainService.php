<?php

namespace App\Domain\Player\Service;

use App\Domain\Player\ValueObject\PlayerId;

/**
 * PlayerAttributeDomainService handles player attribute domain logic
 *
 * Pure domain service that encapsulates business rules for player
 * attribute generation, validation, and management. Contains
 * domain knowledge about player attributes.
 * Uses modern PHP 8.4 features for cleaner API.
 */
class PlayerAttributeDomainService
{
    /** @var array Available player colors */
    private const array PLAYER_COLORS = [
        0xFF6B6B, // Red
        0x4ECDC4, // Teal
        0x45B7D1, // Blue
        0x96CEB4, // Green
        0xFECA57, // Yellow
        0xFF9FF3, // Pink
        0x54A0FF, // Light Blue
        0x5F27CD  // Purple
    ];

    /** @var array Color names mapping for quick access */
    private const array COLOR_NAMES = [
        0xFF6B6B => 'Red',
        0x4ECDC4 => 'Teal',
        0x45B7D1 => 'Blue',
        0x96CEB4 => 'Green',
        0xFECA57 => 'Yellow',
        0xFF9FF3 => 'Pink',
        0x54A0FF => 'Light Blue',
        0x5F27CD => 'Purple',
    ];

    /** @var int Minimum player name length */
    private const int MIN_NAME_LENGTH = 1;

    /** @var int Maximum player name length */
    private const int MAX_NAME_LENGTH = 50;

    // Modern property hooks for configuration access
    public array $availableColors {
        get => self::PLAYER_COLORS;
    }

    public array $colorNames {
        get => self::COLOR_NAMES;
    }

    public int $minNameLength {
        get => self::MIN_NAME_LENGTH;
    }

    public int $maxNameLength {
        get => self::MAX_NAME_LENGTH;
    }

    public int $totalAvailableColors {
        get => count(self::PLAYER_COLORS);
    }

    /**
     * Generates unique player ID according to domain rules
     *
     * @return PlayerId Unique player identifier
     */
    public function generatePlayerId(): PlayerId
    {
        return PlayerId::generate();
    }

    /**
     * Generates random player color from available colors
     *
     * @return int Player color as hexadecimal value
     */
    public function generatePlayerColor(): int
    {
        return $this->availableColors[array_rand($this->availableColors)];
    }

    /**
     * Validates player name according to domain rules
     *
     * @param string $name Player name to validate
     * @return bool True if name is valid
     */
    public function isValidPlayerName(string $name): bool
    {
        $trimmedName = trim($name);
        return strlen($trimmedName) >= $this->minNameLength &&
            strlen($trimmedName) <= $this->maxNameLength;
    }

    /**
     * Validates player color according to domain rules
     *
     * @param int $color Color to validate
     * @return bool True if color is valid
     */
    public function isValidPlayerColor(int $color): bool
    {
        // Use PHP 8.4 array_any instead of in_array for consistency with modern patterns
        return array_any($this->availableColors, fn($availableColor) => $availableColor === $color);
    }

    /**
     * Gets all available player colors
     *
     * @return array Array of available color values
     */
    public function getAvailableColors(): array
    {
        return $this->availableColors;
    }

    /**
     * Normalizes player name according to domain rules
     *
     * @param string $name Raw player name
     * @return string Normalized player name
     */
    public function normalizePlayerName(string $name): string
    {
        return trim($name);
    }

    /**
     * Gets color name for display purposes
     *
     * @param int $color Color value
     * @return string Human-readable color name
     */
    public function getColorName(int $color): string
    {
        return $this->colorNames[$color] ?? 'Unknown';
    }

    /**
     * Gets color information as associative array
     */
    public function getColorInfo(int $color): array
    {
        return [
            'value' => $color,
            'name' => $this->getColorName($color),
            'hex' => sprintf('#%06X', $color),
            'isValid' => $this->isValidPlayerColor($color)
        ];
    }

    /**
     * Gets all color information
     */
    public function getAllColorsInfo(): array
    {
        return array_map(
            fn(int $color) => $this->getColorInfo($color),
            $this->availableColors
        );
    }
}
