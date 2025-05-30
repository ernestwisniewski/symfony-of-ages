<?php

namespace App\Application\Player\Service;

use App\Application\Map\Service\HexNeighborService;
use App\Domain\Map\Enum\TerrainType;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Service\PlayerAttributeDomainService;
use App\Domain\Player\Service\PlayerTurnDomainService;
use App\Domain\Player\ValueObject\Position;
use App\Domain\Shared\Service\HexGridService;

/**
 * PlayerService serves as a facade for player-related operations
 *
 * Orchestrates between specialized player services to provide a unified interface
 * for player operations. Provides domain-focused high-level operations while
 * delegating responsibilities to focused services following SOLID principles.
 */
class PlayerService
{
    public function __construct(
        private readonly PlayerCreationService        $creationService,
        private readonly PlayerMovementService        $movementService,
        private readonly PlayerTurnService            $turnService,
        private readonly PlayerPositionService        $positionService,
        private readonly PlayerAttributeService       $attributeService,
        private readonly MovementCalculationService   $movementCalculationService,
        private readonly HexGridService               $hexGridService,
        private readonly PlayerTurnDomainService      $turnDomainService,
        private readonly PlayerAttributeDomainService $attributeDomainService,
        private readonly HexNeighborService           $hexNeighborService
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
    public function createTestPlayer(string $name = 'Test Player', ?Position $position = null): Player
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
     * Starts a new turn for the player using domain service
     */
    public function startPlayerTurn(Player $player): void
    {
        $this->turnDomainService->startNewTurn($player);
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
     * Checks if player has movement points remaining using domain service
     */
    public function canPlayerContinueTurn(Player $player): bool
    {
        return $this->turnDomainService->canPlayerContinueTurn($player);
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
     * Gets available player colors using domain service
     */
    public function getAvailablePlayerColors(): array
    {
        return $this->attributeDomainService->getAvailableColors();
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
        $currentTerrain = $mapData[$position->row][$position->col];

        $surroundingAnalysis = $this->analyzeSurroundingTerrain($position, $mapData, $mapRows, $mapCols);
        $movementOptions = $this->calculateMovementOptions($player, $mapData, $mapRows, $mapCols);

        return [
            'current_position' => $position->toArray(),
            'current_terrain' => $currentTerrain,
            'movement_points' => [
                'current' => $player->currentMovementPoints,
                'maximum' => $player->maxMovementPoints
            ],
            'surrounding_terrain' => $surroundingAnalysis,
            'movement_options' => $movementOptions,
            'tactical_advantages' => $this->identifyTacticalAdvantages($currentTerrain, $surroundingAnalysis),
            'recommendations' => $this->generateTacticalRecommendations($player, $movementOptions, $surroundingAnalysis),
            'turn_efficiency' => $this->turnDomainService->calculateMovementEfficiency($player)
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
        $terrain = $mapData[$position->row][$position->col];

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
        $movementPercentage = $player->maxMovementPoints > 0
            ? ($player->currentMovementPoints / $player->maxMovementPoints) * 100
            : 0;

        return [
            'basic_info' => [
                'id' => $player->getId()->value,
                'name' => $player->getName(),
                'color' => $player->getColor(),
                'color_name' => $this->attributeDomainService->getColorName($player->getColor())
            ],
            'position' => $player->getPosition()->toArray(),
            'movement' => [
                'current_points' => $player->currentMovementPoints,
                'maximum_points' => $player->maxMovementPoints,
                'can_move' => $player->canContinueTurn(),
                'movement_percentage' => $movementPercentage,
                'efficiency' => $this->turnDomainService->calculateMovementEfficiency($player)
            ],
            'turn_status' => [
                'can_continue' => $this->turnDomainService->canPlayerContinueTurn($player),
                'should_end_turn' => $this->turnDomainService->shouldEndTurn($player)
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

    // Private helper methods using separated grid and map services

    /**
     * Analyzes terrain surrounding player position
     */
    private function analyzeSurroundingTerrain(Position $position, array $mapData, int $mapRows, int $mapCols): array
    {
        $neighbors = $this->hexNeighborService->getNeighborTiles($mapData, $position, $mapRows, $mapCols);
        $terrainType = TerrainType::from($mapData[$position->row][$position->col]['type']);

        return [
            'current' => [
                'type' => $terrainType->value,
                'name' => $terrainType->getProperties()->name,
                'movementCost' => $terrainType->getProperties()->movementCost,
                'defensiveValue' => $terrainType->getProperties()->defenseBonus,
                'economicValue' => $terrainType->getProperties()->resourceYield,
                'passable' => $terrainType->getProperties()->isPassable
            ],
            'neighbors' => array_map(function ($neighbor) {
                $neighborTerrainType = TerrainType::from($neighbor['type']);
                return [
                    'type' => $neighborTerrainType->value,
                    'name' => $neighborTerrainType->getProperties()->name,
                    'position' => $neighbor['coordinates'],
                    'movementCost' => $neighborTerrainType->getProperties()->movementCost,
                    'passable' => $neighborTerrainType->getProperties()->isPassable
                ];
            }, $neighbors)
        ];
    }

    /**
     * Calculates available movement options using hex grid service
     */
    private function calculateMovementOptions(Player $player, array $mapData, int $mapRows, int $mapCols): array
    {
        $options = [];
        $currentPosition = $player->getPosition();
        $adjacentPositions = $this->hexGridService->getAdjacentPositions($currentPosition, $mapRows, $mapCols);

        foreach ($adjacentPositions as $position) {
            $terrainData = $mapData[$position->row][$position->col];
            $terrainType = TerrainType::from($terrainData['type']);
            $movementCost = $terrainType->getProperties()->movementCost;

            if (!$terrainType->getProperties()->isPassable || !$player->canMoveTo($movementCost)) {
                continue;
            }

            $options[] = [
                'position' => $position,
                'terrain' => $terrainData,
                'movementCost' => $movementCost,
                'defensiveValue' => $terrainType->getProperties()->defenseBonus,
                'economicValue' => $terrainType->getProperties()->resourceYield,
                'remainingMovement' => $player->currentMovementPoints - $movementCost
            ];
        }

        return $options;
    }

    /**
     * Identifies tactical advantages of current position
     */
    private function identifyTacticalAdvantages(array $currentTerrain, array $surroundingAnalysis): array
    {
        $advantages = [];
        $currentTerrainType = TerrainType::from($currentTerrain['type']);

        // High ground advantage
        if ($currentTerrainType->getProperties()->defenseBonus >= 3) {
            $advantages[] = 'High defensive position';
        }

        // Economic advantage
        if ($currentTerrainType->getProperties()->resourceYield >= 3) {
            $advantages[] = 'Resource-rich location';
        }

        return $advantages;
    }

    /**
     * Generates tactical recommendations
     */
    private function generateTacticalRecommendations(Player $player, array $movementOptions, array $surroundingAnalysis): array
    {
        $recommendations = [];

        // Find best defensive position using PHP 8.4 array_find
        $bestDefensiveOption = array_find(
            $movementOptions,
            fn($option) => $option['defensiveValue'] === max(array_column($movementOptions, 'defensiveValue'))
        );

        if ($bestDefensiveOption) {
            $recommendations[] = "Move to high ground for defense: " . $bestDefensiveOption['terrain']['name'];
        }

        // Find best economic position using PHP 8.4 array_find
        $bestEconomicOption = array_find(
            $movementOptions,
            fn($option) => $option['economicValue'] === max(array_column($movementOptions, 'economicValue'))
        );

        if ($bestEconomicOption && $bestEconomicOption !== $bestDefensiveOption) {
            $recommendations[] = "Move to resource-rich terrain: " . $bestEconomicOption['terrain']['name'];
        }

        // Find cheapest movement option using PHP 8.4 array_find
        $cheapestOption = array_find(
            $movementOptions,
            fn($option) => $option['movementCost'] === min(array_column($movementOptions, 'movementCost'))
        );

        if ($cheapestOption && $cheapestOption !== $bestDefensiveOption && $cheapestOption !== $bestEconomicOption) {
            $recommendations[] = "Conserve movement: " . $cheapestOption['terrain']['name'];
        }

        // Check if all options are expensive using PHP 8.4 array_all
        if (array_all($movementOptions, fn($option) => $option['movementCost'] >= 3)) {
            $recommendations[] = "All moves are expensive - consider ending turn to restore movement points";
        }

        return $recommendations;
    }

    /**
     * Finds the best movement options by category
     */
    private function findBestMovementOptions(array $validOptions): array
    {
        if (empty($validOptions)) {
            return [];
        }

        // Sort by combined value (defensive + economic - movement cost)
        usort($validOptions, function ($a, $b) {
            $scoreA = $a['defensiveValue'] + $a['economicValue'] - $a['movementCost'];
            $scoreB = $b['defensiveValue'] + $b['economicValue'] - $b['movementCost'];
            return $scoreB <=> $scoreA;
        });

        return array_slice($validOptions, 0, 3); // Return top 3 options
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
        if ($option && !in_array($option, $recommendations)) {
            $recommendations[] = "{$type}: " . $option['terrain']['name'];
        }
    }

    /**
     * Helper method to get terrain property value
     */
    private function getTerrainProperty(array $option, string $property): mixed
    {
        return TerrainType::from($option['terrain']['type'])->getProperties()->{$property}();
    }
}
