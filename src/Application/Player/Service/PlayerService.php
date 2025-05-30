<?php

namespace App\Application\Player\Service;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\TerrainType;
use App\Domain\Player\ValueObject\Position;
use App\Application\Game\Service\MovementCalculationService;

/**
 * PlayerService serves as a facade for player-related operations
 *
 * Orchestrates between specialized player services to provide a unified interface
 * for player operations. Provides domain-focused high-level operations while
 * delegating responsibilities to focused services following SOLID principles.
 */
class PlayerService
{
    /** @var array Simple grid directions for tactical analysis (placeholder for hex neighbor service) */
    private const array GRID_DIRECTIONS = [
        [-1, -1], [-1, 0], [-1, 1],
        [0, -1], [0, 1],
        [1, -1], [1, 0], [1, 1]
    ];

    public function __construct(
        private readonly PlayerCreationService       $creationService,
        private readonly PlayerMovementService       $movementService,
        private readonly PlayerTurnService           $turnService,
        private readonly PlayerPositionService       $positionService,
        private readonly PlayerAttributeService      $attributeService,
        private readonly MovementCalculationService  $movementCalculationService
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
     * @return Player New player instance
     */
    public function createPlayer(string $name, int $mapRows, int $mapCols, array $mapData): Player
    {
        return $this->creationService->createPlayer($name, $mapRows, $mapCols, $mapData);
    }

    /**
     * Creates a test player for development and testing purposes
     *
     * @param string $name Player name (optional)
     * @param Position|null $position Starting position (optional)
     * @return Player Test player instance
     */
    public function createTestPlayer(string $name = 'Test Player', Position $position = null): Player
    {
        return $this->creationService->createTestPlayer($name, $position);
    }

    /**
     * Attempts to move player to target position
     *
     * @param Player $player Player to move
     * @param Position $targetPosition Target position
     * @param array $mapData Map terrain data
     * @return array Movement result with success status and message
     */
    public function movePlayer(Player $player, Position $targetPosition, array $mapData): array
    {
        return $this->movementService->movePlayer($player, $targetPosition, $mapData);
    }

    /**
     * Starts a new turn for the player
     */
    public function startPlayerTurn(Player $player): void
    {
        $this->turnService->startPlayerTurn($player);
    }

    /**
     * Ends the current turn for the player
     */
    public function endPlayerTurn(Player $player): void
    {
        $this->turnService->endPlayerTurn($player);
    }

    // Domain-focused convenience methods

    /**
     * Checks if player can move to a position with specified movement cost
     */
    public function canPlayerMoveToPosition(Player $player, int $movementCost): bool
    {
        return $this->movementService->canPlayerMoveToPosition($player, $movementCost);
    }

    /**
     * Checks if player has movement points remaining
     */
    public function canPlayerContinueTurn(Player $player): bool
    {
        return $this->turnService->canPlayerContinueTurn($player);
    }

    /**
     * Validates if two positions are adjacent
     */
    public function arePositionsAdjacent(Position $from, Position $to): bool
    {
        return $this->movementService->arePositionsAdjacent($from, $to);
    }

    /**
     * Gets terrain movement cost
     */
    public function getTerrainMovementCost(array $terrainData): int
    {
        return $this->movementService->getTerrainMovementCost($terrainData);
    }

    /**
     * Gets available player colors
     */
    public function getAvailablePlayerColors(): array
    {
        return $this->attributeService->getAvailableColors();
    }

    /**
     * Analyzes player's current tactical situation
     *
     * Provides tactical information about player's current position,
     * movement options, and surrounding terrain.
     *
     * @param Player $player Player to analyze
     * @param array $mapData Complete map data
     * @param int $mapRows Number of rows in the map
     * @param int $mapCols Number of columns in the map
     * @return array Tactical analysis
     */
    public function analyzePlayerTacticalSituation(Player $player, array $mapData, int $mapRows, int $mapCols): array
    {
        $position = $player->getPosition();
        $currentTerrain = $mapData[$position->getRow()][$position->getCol()];

        $surroundingAnalysis = $this->analyzeSurroundingTerrain($position, $mapData, $mapRows, $mapCols);
        $movementOptions = $this->calculateMovementOptions($player, $mapData, $mapRows, $mapCols);

        return [
            'current_position' => $position->toArray(),
            'current_terrain' => $currentTerrain,
            'movement_points' => [
                'current' => $player->getMovementPoints(),
                'maximum' => $player->getMaxMovementPoints()
            ],
            'surrounding_terrain' => $surroundingAnalysis,
            'movement_options' => $movementOptions,
            'tactical_advantages' => $this->identifyTacticalAdvantages($currentTerrain, $surroundingAnalysis),
            'recommendations' => $this->generateTacticalRecommendations($player, $movementOptions, $surroundingAnalysis)
        ];
    }

    /**
     * Validates if a position is suitable for player operations
     *
     * @param Position $position Position to validate
     * @param array $mapData Map terrain data
     * @param int $mapRows Number of rows in the map
     * @param int $mapCols Number of columns in the map
     * @return array Validation result with details
     */
    public function validatePlayerPosition(Position $position, array $mapData, int $mapRows, int $mapCols): array
    {
        if (!$this->positionService->isValidMapPosition($position, $mapRows, $mapCols)) {
            return [
                'valid' => false,
                'reason' => 'Position is outside map bounds',
                'code' => 'OUT_OF_BOUNDS'
            ];
        }

        $isValidForStarting = $this->positionService->isValidStartingPosition($position, $mapData);
        $terrain = $mapData[$position->getRow()][$position->getCol()];

        return [
            'valid' => $isValidForStarting,
            'reason' => $isValidForStarting ? 'Position is suitable' : 'Position has impassable terrain',
            'code' => $isValidForStarting ? 'VALID' : 'IMPASSABLE_TERRAIN',
            'terrain' => $terrain,
            'movement_cost' => $this->getTerrainMovementCost($terrain)
        ];
    }

    /**
     * Gets comprehensive player status for UI display
     *
     * @param Player $player Player to get status for
     * @return array Comprehensive player status
     */
    public function getPlayerStatus(Player $player): array
    {
        $movementPercentage = $player->getMaxMovementPoints() > 0
            ? ($player->getMovementPoints() / $player->getMaxMovementPoints()) * 100
            : 0;

        return [
            'basic_info' => [
                'id' => $player->getId()->getValue(),
                'name' => $player->getName(),
                'color' => $player->getColor()
            ],
            'position' => $player->getPosition()->toArray(),
            'movement' => [
                'current_points' => $player->getMovementPoints(),
                'maximum_points' => $player->getMaxMovementPoints(),
                'can_move' => $player->canContinueTurn(),
                'movement_percentage' => $movementPercentage
            ],
            'turn_status' => [
                'can_continue' => $this->canPlayerContinueTurn($player),
                'should_end_turn' => $this->turnService->shouldEndTurn($player)
            ]
        ];
    }

    /**
     * Calculates possible moves for player
     *
     * @param Player $player Player for whom we calculate moves
     * @param array $mapData Map terrain data
     * @param int $mapRows Number of map rows
     * @param int $mapCols Number of map columns
     * @return array Player's possible moves
     */
    public function calculatePlayerPossibleMoves(Player $player, array $mapData, int $mapRows, int $mapCols): array
    {
        return $this->movementCalculationService->calculatePossibleMoves($player, $mapData, $mapRows, $mapCols);
    }

    /**
     * Calculates detailed movement options for player
     *
     * @param Player $player Player for whom we calculate movement options
     * @param array $mapData Map terrain data
     * @param int $mapRows Number of map rows
     * @param int $mapCols Number of map columns
     * @return array Detailed movement options
     */
    public function calculatePlayerMovementOptions(Player $player, array $mapData, int $mapRows, int $mapCols): array
    {
        return $this->movementCalculationService->calculateDetailedMovementOptions($player, $mapData, $mapRows, $mapCols);
    }

    /**
     * Checks if player can move to specific position (with terrain map)
     *
     * @param Player $player Player
     * @param Position $targetPosition Target position
     * @param array $mapData Map data
     * @return array Information about movement possibility
     */
    public function canPlayerMoveToSpecificPosition(Player $player, Position $targetPosition, array $mapData): array
    {
        return $this->movementCalculationService->canPlayerMoveTo($player, $targetPosition, $mapData);
    }

    // Private helper methods

    /**
     * Analyzes terrain surrounding player position
     */
    private function analyzeSurroundingTerrain(Position $position, array $mapData, int $mapRows, int $mapCols): array
    {
        $surroundingTerrain = [];
        $terrainCounts = [];

        foreach (self::GRID_DIRECTIONS as $direction) {
            $newRow = $position->getRow() + $direction[0];
            $newCol = $position->getCol() + $direction[1];

            if ($this->isWithinBounds($newRow, $newCol, $mapRows, $mapCols)) {
                $terrain = $mapData[$newRow][$newCol];
                $surroundingTerrain[] = $terrain;
                $terrainType = $terrain['type'];
                $terrainCounts[$terrainType] = ($terrainCounts[$terrainType] ?? 0) + 1;
            }
        }

        return [
            'adjacent_tiles' => $surroundingTerrain,
            'terrain_distribution' => $terrainCounts,
            'dominant_terrain' => !empty($terrainCounts) ? array_keys($terrainCounts, max($terrainCounts))[0] : null
        ];
    }

    /**
     * Calculates available movement options
     */
    private function calculateMovementOptions(Player $player, array $mapData, int $mapRows, int $mapCols): array
    {
        $movementOptions = [];
        $currentPosition = $player->getPosition();
        $currentMovementPoints = $player->getMovementPoints();

        foreach (self::GRID_DIRECTIONS as $direction) {
            $newRow = $currentPosition->getRow() + $direction[0];
            $newCol = $currentPosition->getCol() + $direction[1];

            if ($this->isWithinBounds($newRow, $newCol, $mapRows, $mapCols)) {
                $terrain = $mapData[$newRow][$newCol];
                $movementCost = $this->getTerrainMovementCost($terrain);

                $movementOptions[] = [
                    'position' => ['row' => $newRow, 'col' => $newCol],
                    'terrain' => $terrain,
                    'movement_cost' => $movementCost,
                    'can_move' => $movementCost > 0 && $currentMovementPoints >= $movementCost,
                    'movement_points_after' => max(0, $currentMovementPoints - $movementCost)
                ];
            }
        }

        return $movementOptions;
    }

    /**
     * Identifies tactical advantages of current position
     */
    private function identifyTacticalAdvantages(array $currentTerrain, array $surroundingAnalysis): array
    {
        $advantages = [];
        $terrainType = TerrainType::from($currentTerrain['type']);
        $properties = $terrainType->getProperties();

        $this->addAdvantageIfMet($advantages, $properties['defense'] > 2, 'High defensive position');
        $this->addAdvantageIfMet($advantages, $properties['resources'] > 2, 'Resource-rich location');
        $this->addAdvantageIfMet($advantages, $properties['movementCost'] === 1, 'High mobility terrain');

        $dominantTerrain = $surroundingAnalysis['dominant_terrain'] ?? null;
        $this->addAdvantageIfMet(
            $advantages,
            $dominantTerrain === TerrainType::WATER->value,
            'Near water (strategic chokepoint)'
        );

        return $advantages;
    }

    /**
     * Generates tactical recommendations
     */
    private function generateTacticalRecommendations(Player $player, array $movementOptions, array $surroundingAnalysis): array
    {
        if ($player->getMovementPoints() === 0) {
            return ['Start new turn to restore movement points'];
        }

        $validOptions = array_filter($movementOptions, fn($option) => $option['can_move']);

        if (empty($validOptions)) {
            return ['No valid movement options available'];
        }

        $recommendations = [];
        $bestOptions = $this->findBestMovementOptions($validOptions);

        $this->addRecommendationIfDifferent($recommendations, $bestOptions['defensive'], 'defense');
        $this->addRecommendationIfDifferent($recommendations, $bestOptions['resource'], 'resources');

        return $recommendations;
    }

    /**
     * Finds the best movement options by category
     */
    private function findBestMovementOptions(array $validOptions): array
    {
        $bestDefensive = null;
        $bestResource = null;
        $bestMobility = null;

        foreach ($validOptions as $option) {
            $terrainType = TerrainType::from($option['terrain']['type']);
            $properties = $terrainType->getProperties();

            if (!$bestDefensive || $properties['defense'] > $this->getTerrainProperty($bestDefensive, 'defense')) {
                $bestDefensive = $option;
            }

            if (!$bestResource || $properties['resources'] > $this->getTerrainProperty($bestResource, 'resources')) {
                $bestResource = $option;
            }

            if (!$bestMobility || $properties['movementCost'] < $this->getTerrainProperty($bestMobility, 'movementCost')) {
                $bestMobility = $option;
            }
        }

        return [
            'defensive' => $bestDefensive,
            'resource' => $bestResource,
            'mobility' => $bestMobility
        ];
    }

    /**
     * Helper method to check if coordinates are within map bounds
     */
    private function isWithinBounds(int $row, int $col, int $mapRows, int $mapCols): bool
    {
        return $row >= 0 && $row < $mapRows && $col >= 0 && $col < $mapCols;
    }

    /**
     * Helper method to add advantage if condition is met
     */
    private function addAdvantageIfMet(array &$advantages, bool $condition, string $advantage): void
    {
        if ($condition) {
            $advantages[] = $advantage;
        }
    }

    /**
     * Helper method to add recommendation if option is different from previous
     */
    private function addRecommendationIfDifferent(array &$recommendations, ?array $option, string $type): void
    {
        if ($option) {
            $recommendations[] = "Consider moving to {$option['terrain']['name']} for better {$type}";
        }
    }

    /**
     * Helper method to get terrain property value
     */
    private function getTerrainProperty(array $option, string $property): mixed
    {
        return TerrainType::from($option['terrain']['type'])->getProperties()[$property];
    }
}
