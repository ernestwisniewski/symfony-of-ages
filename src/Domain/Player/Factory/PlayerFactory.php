<?php

namespace App\Domain\Player\Factory;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Service\PlayerAttributeDomainService;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Player\ValueObject\Position;

/**
 * PlayerFactory handles complex player creation logic
 *
 * Domain factory that encapsulates the knowledge of how to create
 * valid Player entities with all required constraints and validations.
 * Follows DDD Factory pattern for complex object creation.
 */
class PlayerFactory
{
    public function __construct(
        private readonly PlayerAttributeDomainService $attributeDomainService
    ) {
    }

    /**
     * Creates a new player with auto-generated ID and random color
     *
     * @param string $name Player name
     * @param Position $position Starting position
     * @param int $maxMovementPoints Maximum movement points per turn
     * @return Player New player instance
     */
    public function createPlayer(
        string   $name,
        Position $position,
        int      $maxMovementPoints = 3
    ): Player {
        $playerId = PlayerId::generate();
        $color = $this->attributeDomainService->generatePlayerColor();

        return new Player(
            $playerId,
            $position,
            $name,
            $maxMovementPoints,
            $color
        );
    }

    /**
     * Creates a player with specific attributes
     *
     * @param PlayerId $id Player ID
     * @param string $name Player name
     * @param Position $position Starting position
     * @param int $maxMovementPoints Maximum movement points per turn
     * @param int $color Player color
     * @return Player New player instance
     */
    public function createPlayerWithAttributes(
        PlayerId $id,
        string   $name,
        Position $position,
        int      $maxMovementPoints = 3,
        int      $color = 0xFF6B6B
    ): Player {
        return new Player(
            $id,
            $position,
            $name,
            $maxMovementPoints,
            $color
        );
    }

    /**
     * Creates a player for testing purposes with predefined values
     *
     * @param string $name Player name (optional)
     * @param Position|null $position Starting position (optional)
     * @return Player Test player instance
     */
    public function createTestPlayer(
        string    $name = 'Test Player',
        ?Position $position = null
    ): Player {
        $position = $position ?? new Position(50, 50);

        return $this->createPlayer($name, $position, 3);
    }

    /**
     * Gets all available player colors using domain service
     *
     * @return array Array of color values
     */
    public function getAvailableColors(): array
    {
        return $this->attributeDomainService->getAvailableColors();
    }
}
