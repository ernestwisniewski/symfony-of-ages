<?php

namespace App\Domain\Player\Service;

use App\Domain\Player\ValueObject\PlayerId;

/**
 * PlayerAttributeDomainService handles player attribute domain logic
 *
 * Pure domain service that encapsulates business rules for player
 * attribute generation, validation, and management. Contains
 * domain knowledge about player attributes.
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

    /** @var int Minimum player name length */
    private const int MIN_NAME_LENGTH = 1;

    /** @var int Maximum player name length */
    private const int MAX_NAME_LENGTH = 50;

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
        return self::PLAYER_COLORS[array_rand(self::PLAYER_COLORS)];
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
        return strlen($trimmedName) >= self::MIN_NAME_LENGTH &&
            strlen($trimmedName) <= self::MAX_NAME_LENGTH;
    }

    /**
     * Validates player color according to domain rules
     *
     * @param int $color Color to validate
     * @return bool True if color is valid
     */
    public function isValidPlayerColor(int $color): bool
    {
        return in_array($color, self::PLAYER_COLORS);
    }

    /**
     * Gets all available player colors
     *
     * @return array Array of available color values
     */
    public function getAvailableColors(): array
    {
        return self::PLAYER_COLORS;
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
        return match ($color) {
            0xFF6B6B => 'Red',
            0x4ECDC4 => 'Teal',
            0x45B7D1 => 'Blue',
            0x96CEB4 => 'Green',
            0xFECA57 => 'Yellow',
            0xFF9FF3 => 'Pink',
            0x54A0FF => 'Light Blue',
            0x5F27CD => 'Purple',
            default => 'Unknown'
        };
    }
}
