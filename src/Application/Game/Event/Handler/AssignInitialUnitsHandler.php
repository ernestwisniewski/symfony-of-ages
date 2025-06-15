<?php

namespace App\Application\Game\Event\Handler;

use App\Application\Unit\Command\CreateUnitCommand;
use App\Application\Game\Query\GetGamePlayersQuery;
use App\Domain\Game\Event\GameWasStarted;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
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
        private QueryBus   $queryBus
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
        $createdAt = Timestamp::now();
        
        // Create a warrior for the player
        $this->commandBus->send(new CreateUnitCommand(
            unitId: new UnitId(Uuid::v4()->toRfc4122()),
            ownerId: $playerId,
            gameId: $gameId,
            type: UnitType::WARRIOR,
            position: $this->getStartingPosition($playerId, 'warrior'),
            createdAt: $createdAt
        ));
        
        // Create a settler for the player
        $this->commandBus->send(new CreateUnitCommand(
            unitId: new UnitId(Uuid::v4()->toRfc4122()),
            ownerId: $playerId,
            gameId: $gameId,
            type: UnitType::SETTLER,
            position: $this->getStartingPosition($playerId, 'settler'),
            createdAt: $createdAt
        ));
    }
    
    private function getStartingPosition(PlayerId $playerId, string $unitType): Position
    {
        // Simple starting position logic - in a real game, this would be more sophisticated
        // For now, we'll place units at different positions based on player ID hash
        $playerHash = crc32((string)$playerId);
        $baseX = ($playerHash % 10) + 1; // X position between 1-10
        $baseY = (($playerHash >> 8) % 10) + 1; // Y position between 1-10
        
        // Offset the settler slightly from the warrior
        $offsetX = $unitType === 'settler' ? 1 : 0;
        $offsetY = $unitType === 'settler' ? 1 : 0;
        
        return new Position($baseX + $offsetX, $baseY + $offsetY);
    }
} 