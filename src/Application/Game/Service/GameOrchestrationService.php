<?php

namespace App\Application\Game\Service;

use App\Application\City\Command\FoundCityCommand;
use App\Application\Game\Command\JoinGameCommand;
use App\Application\Game\Command\StartGameCommand;
use App\Application\Map\Query\GetMapTilesQuery;
use App\Domain\City\Service\CityManagementService;
use App\Domain\City\ValueObject\CityId;
use App\Domain\City\ValueObject\CityName;
use App\Domain\City\ValueObject\Position;
use App\Domain\Game\Service\GameManagementService;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Map\ValueObject\TerrainType;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;
use Symfony\Component\Uid\Uuid;

final readonly class GameOrchestrationService
{
    public function __construct(
        private GameManagementService $gameManagementService,
        private CityManagementService $cityManagementService,
        private CommandBus            $commandBus,
        private QueryBus              $queryBus
    )
    {
    }

    public function joinGameIfPossible(GameId $gameId, PlayerId $playerId): array
    {
        // This would typically get game state from a repository
        // For demo purposes, using mock data
        $existingPlayers = []; // TODO: Get from repository
        $startedAt = null; // TODO: Get from repository

        if (!$this->gameManagementService->canPlayerJoin($playerId, $existingPlayers, $startedAt)) {
            return [
                'success' => false,
                'reason' => 'Cannot join game - check if game is started or player already joined'
            ];
        }

        $this->commandBus->send(new JoinGameCommand($gameId, $playerId));

        return ['success' => true, 'message' => 'Player joined successfully'];
    }

    public function startGameIfReady(GameId $gameId): array
    {
        // Get current game state
        $playersCount = 2; // TODO: Get from repository
        $startedAt = null; // TODO: Get from repository

        if (!$this->gameManagementService->canGameStart($playersCount, $startedAt)) {
            return [
                'success' => false,
                'reason' => 'Cannot start game - insufficient players or already started'
            ];
        }

        $this->commandBus->send(new StartGameCommand($gameId, Timestamp::now()));

        return ['success' => true, 'message' => 'Game started successfully'];
    }

    public function foundCityAtBestPosition(
        GameId   $gameId,
        PlayerId $playerId,
        CityName $cityName
    ): array
    {
        // Get map tiles for the game
        $mapTiles = $this->queryBus->send(new GetMapTilesQuery($gameId));

        // Get existing city positions (this should come from a repository)
        $existingCityPositions = []; // TODO: Get from city repository

        // Find suitable positions using the policy
        $suitablePositions = $this->cityManagementService->findSuitablePositions(
            $mapTiles,
            $existingCityPositions
        );

        if (empty($suitablePositions)) {
            return [
                'success' => false,
                'reason' => 'No suitable positions found for city founding'
            ];
        }

        // Choose the first suitable position (in real app, you might have more sophisticated logic)
        $chosenPosition = $suitablePositions[0];

        // Determine terrain at that position
        $terrain = $this->getTerrainAtPosition($mapTiles, $chosenPosition);

        $this->commandBus->send(new FoundCityCommand(
            new CityId(Uuid::v4()->toRfc4122()),
            $playerId,
            $cityName,
            $chosenPosition,
            $terrain,
            $existingCityPositions
        ));

        return [
            'success' => true,
            'message' => 'City founded successfully',
            'position' => $chosenPosition->toArray(),
            'terrain' => $terrain->value
        ];
    }

    private function getTerrainAtPosition(array $mapTiles, Position $position): TerrainType
    {
        foreach ($mapTiles as $tile) {
            if ($tile->x === $position->x && $tile->y === $position->y) {
                return TerrainType::from($tile->terrain);
            }
        }

        // Fallback to plains if not found
        return TerrainType::PLAINS;
    }
}
