<?php

namespace App\Application\Player\Service;

use App\Domain\Player\Service\PlayerAttributeDomainService;
use InvalidArgumentException;

/**
 * PlayerAttributeService handles player attribute coordination
 *
 * Application service that coordinates player attribute operations
 * and delegates domain logic to PlayerAttributeDomainService.
 */
class PlayerAttributeService
{
    public function __construct(
        private readonly PlayerAttributeDomainService $attributeDomainService
    )
    {
    }

    /**
     * Generates unique player ID
     *
     * @return string Unique player identifier
     */
    public function generatePlayerId(): string
    {
        return $this->attributeDomainService->generatePlayerId()->value;
    }

    /**
     * Generates random player color
     *
     * @return int Player color as hexadecimal value
     */
    public function generatePlayerColor(): int
    {
        return $this->attributeDomainService->generatePlayerColor();
    }

    /**
     * Gets all available player colors
     *
     * @return array Array of available color values
     */
    public function getAvailableColors(): array
    {
        return $this->attributeDomainService->getAvailableColors();
    }

    /**
     * Validates and normalizes player name
     *
     * @param string $name Raw player name
     * @return string Normalized player name
     * @throws InvalidArgumentException If name is invalid
     */
    public function validateAndNormalizeName(string $name): string
    {
        $normalizedName = $this->attributeDomainService->normalizePlayerName($name);

        if (!$this->attributeDomainService->isValidPlayerName($normalizedName)) {
            throw new InvalidArgumentException('Invalid player name');
        }

        return $normalizedName;
    }

    /**
     * Validates player color
     *
     * @param int $color Color to validate
     * @return bool True if color is valid
     */
    public function isValidColor(int $color): bool
    {
        return $this->attributeDomainService->isValidPlayerColor($color);
    }

    /**
     * Gets human-readable color name
     *
     * @param int $color Color value
     * @return string Color name for display
     */
    public function getColorName(int $color): string
    {
        return $this->attributeDomainService->getColorName($color);
    }
}
