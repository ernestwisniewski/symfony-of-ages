<?php

namespace App\Application\Game\Service;

use App\Application\City\Command\FoundCityCommand;
use App\Application\Game\Command\JoinGameCommand;
use App\Application\Game\Command\StartGameCommand;
use App\Application\Map\Query\GetMapTilesQuery;
use App\Domain\City\Service\CityManagementService;
use App\Domain\City\ValueObject\CityId;
use App\Domain\City\ValueObject\CityName;
use App\Domain\City\ValueObject\UnitId;
use App\Domain\Game\Service\GameManagementService;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Map\ValueObject\TerrainType;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
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
        $existingPlayers = [];
        $startedAt = null;
        if (!$this->gameManagementService->canPlayerJoin($playerId, $existingPlayers, $startedAt)) {
            return [
                'success' => false,
                'reason' => 'Cannot join game - check if game is started or player already joined'
            ];
        }
        $this->commandBus->send(new JoinGameCommand($gameId, $playerId, Timestamp::now()));
        return ['success' => true, 'message' => 'Player joined successfully'];
    }

    public function startGameIfReady(GameId $gameId): array
    {
        $playersCount = 2;
        $startedAt = null;
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
        UnitId   $unitId,
        CityName $cityName
    ): array
    {
        $mapTiles = $this->queryBus->send(new GetMapTilesQuery($gameId));
        $existingCityPositions = [];
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
        $chosenPosition = $suitablePositions[0];
        $terrain = $this->getTerrainAtPosition($mapTiles, $chosenPosition);
        $this->commandBus->send(new FoundCityCommand(
            new CityId(Uuid::v4()->toRfc4122()),
            $playerId,
            $gameId,
            $unitId,
            $cityName,
            $chosenPosition,
            Timestamp::now(),
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
        return TerrainType::PLAINS;
    }
}
