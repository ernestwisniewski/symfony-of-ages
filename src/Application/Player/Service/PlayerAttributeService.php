<?php

namespace App\Application\Player\Service;

/**
 * PlayerAttributeService handles generation of player attributes
 *
 * Responsible for generating unique player IDs and selecting player colors.
 * Follows Single Responsibility Principle by focusing only on player attribute
 * generation logic separate from other player operations.
 */
class PlayerAttributeService
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

    /**
     * Generates unique player ID
     *
     * @return string Unique player identifier
     */
    public function generatePlayerId(): string
    {
        return 'player_' . uniqid();
    }

    /**
     * Generates random player color
     *
     * @return int Player color as hexadecimal value
     */
    public function generatePlayerColor(): int
    {
        return self::PLAYER_COLORS[array_rand(self::PLAYER_COLORS)];
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
} 