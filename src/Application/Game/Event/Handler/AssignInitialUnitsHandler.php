<?php

namespace App\Application\Game\Event\Handler;

use App\Application\Unit\Command\CreateUnitCommand;
use App\Application\Game\Query\GetGamePlayersQuery;
use App\Domain\Game\Event\GameWasStarted;
use App\Domain\Map\Service\MapGeneratorService;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Unit\ValueObject\UnitId;
use App\Domain\Unit\ValueObject\UnitType;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;
use Symfony\Component\Uid\Uuid;

readonly class AssignInitialUnitsHandler
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private MapGeneratorService $mapGeneratorService
    ) {
    }

    #[EventHandler]
    public function handle(GameWasStarted $event): void
    {
        $gameId = new GameId($event->gameId);
        
        // Get all players in the game
        $players = $this->queryBus->send(new GetGamePlayersQuery($gameId));
        
        // Assign one warrior and one settler to each player
        foreach ($players as $playerId) {
            $this->assignUnitsToPlayer($gameId, new PlayerId($playerId));
        }
    }
    
    private function assignUnitsToPlayer(GameId $gameId, PlayerId $playerId): void
    {
        // Get starting positions from the map generator service
        $warriorPosition = $this->mapGeneratorService->getStartingPosition($playerId, UnitType::WARRIOR->value);
        $settlerPosition = $this->mapGeneratorService->getStartingPosition($playerId, UnitType::SETTLER->value);
        
        $createdAt = Timestamp::now();
        
        // Create a warrior for the player
        $this->commandBus->send(new CreateUnitCommand(
            unitId: new UnitId(Uuid::v4()->toRfc4122()),
            ownerId: $playerId,
            gameId: $gameId,
            type: UnitType::WARRIOR,
            position: $warriorPosition,
            createdAt: $createdAt
        ));
        
        // Create a settler for the player
        $this->commandBus->send(new CreateUnitCommand(
            unitId: new UnitId(Uuid::v4()->toRfc4122()),
            ownerId: $playerId,
            gameId: $gameId,
            type: UnitType::SETTLER,
            position: $settlerPosition,
            createdAt: $createdAt
        ));
    }
} 