<?php

namespace App\Application\Player\Service;

use App\Domain\Game\Factory\PlayerFactory;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\ValueObject\Position;

/**
 * PlayerCreationService handles the creation of new player instances
 *
 * Application service that orchestrates player creation by coordinating
 * between position generation and the domain factory.
 * Follows Single Responsibility Principle by focusing only on player creation.
 */
class PlayerCreationService
{
    public function __construct(
        private readonly PlayerPositionService $positionService,
        private readonly PlayerFactory         $playerFactory
    )
    {
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
        int    $maxMovementPoints = 3
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
        int    $maxMovementPoints = 3
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
    public function createTestPlayer(string $name = 'Test Player', Position $position = null): Player
    {
        return $this->playerFactory->createTestPlayer($name, $position);
    }
}
