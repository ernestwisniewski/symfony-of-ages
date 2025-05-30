<?php

namespace App\Application\Player\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Factory\PlayerFactory;
use App\Domain\Player\ValueObject\Position;

/**
 * PlayerCreationService handles the creation of new player instances
 *
 * Application service that orchestrates player creation by coordinating
 * between position generation and the domain factory.
 * Follows Single Responsibility Principle by focusing only on player creation.
 * Uses modern PHP 8.4 features for cleaner API.
 */
class PlayerCreationService
{
    public const int DEFAULT_MOVEMENT_POINTS = 3;
    private const string DEFAULT_TEST_PLAYER_NAME = 'Test Player';

    public function __construct(
        private readonly PlayerPositionService $positionService,
        private readonly PlayerFactory         $playerFactory
    )
    {
    }

    // Modern property hooks for configuration access
    public int $defaultMovementPoints {
        get => self::DEFAULT_MOVEMENT_POINTS;
    }

    public string $defaultTestPlayerName {
        get => self::DEFAULT_TEST_PLAYER_NAME;
    }

    public array $availableColors {
        get => $this->playerFactory->getAvailableColors();
    }

    /**
     * Creates a new player with random starting position
     *
     * @param string $name Player name
     * @param int $mapRows Number of rows in the map
     * @param int $mapCols Number of columns in the map
     * @param array $mapData Map terrain data for position validation
     * @param int $maxMovementPoints Maximum movement points per turn (default: 3)
     * @return Player New player instance
     */
    public function createPlayer(
        string $name,
        int    $mapRows,
        int    $mapCols,
        array  $mapData,
        int    $maxMovementPoints = self::DEFAULT_MOVEMENT_POINTS
    ): Player
    {
        // Generate starting position
        $position = $this->positionService->generateValidStartingPosition($mapRows, $mapCols, $mapData);

        // Create player using domain factory
        return $this->playerFactory->createPlayer($name, $position, $maxMovementPoints);
    }

    /**
     * Creates a player with specific attributes (for testing or admin purposes)
     *
     * @param string $name Player name
     * @param int $row Starting row position
     * @param int $col Starting column position
     * @param int $maxMovementPoints Maximum movement points per turn
     * @return Player New player instance
     */
    public function createPlayerWithPosition(
        string $name,
        int    $row,
        int    $col,
        int    $maxMovementPoints = self::DEFAULT_MOVEMENT_POINTS
    ): Player
    {
        $position = new Position($row, $col);
        return $this->playerFactory->createPlayer($name, $position, $maxMovementPoints);
    }

    /**
     * Creates a test player for development/testing
     *
     * @param string $name Player name (optional)
     * @param Position|null $position Starting position (optional)
     * @return Player Test player instance
     */
    public function createTestPlayer(
        string    $name = self::DEFAULT_TEST_PLAYER_NAME,
        ?Position $position = null
    ): Player
    {
        return $this->playerFactory->createTestPlayer($name, $position);
    }

    /**
     * Creates multiple test players with sequential naming
     */
    public function createTestPlayers(int $count, string $namePrefix = 'TestPlayer'): array
    {
        return array_map(
            fn(int $index) => $this->createTestPlayer(
                "{$namePrefix}_{$index}",
                new Position(50 + $index, 50 + $index)
            ),
            range(1, $count)
        );
    }

    /**
     * Creates player with modern builder-like pattern
     */
    public function createPlayerWith(): PlayerCreationBuilder
    {
        return new PlayerCreationBuilder($this);
    }
}
