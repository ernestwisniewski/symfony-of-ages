<?php

namespace App\Application\Unit\Event\Handler;

use App\Application\Unit\Query\GetUnitsByGameQuery;
use App\Application\City\Query\GetCitiesByGameQuery;
use App\Application\Visibility\Service\VisibilityApplicationService;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Unit\Event\UnitWasMoved;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\QueryBus;

final readonly class UpdateVisibilityOnUnitMoveHandler
{
    public function __construct(
        private QueryBus $queryBus,
        private VisibilityApplicationService $visibilityService
    ) {
    }

    #[EventHandler]
    public function handle(UnitWasMoved $event): void
    {
        $gameId = new GameId($event->gameId);
        $playerId = new PlayerId($event->ownerId);

        $units = $this->queryBus->send(new GetUnitsByGameQuery($gameId));
        $cities = $this->queryBus->send(new GetCitiesByGameQuery($gameId));

        $playerUnits = array_filter($units, fn($unit) => $unit->ownerId === $event->ownerId);
        $playerCities = array_filter($cities, fn($city) => $city->ownerId === $event->ownerId);

        $this->visibilityService->updatePlayerVisibility(
            $playerId,
            $gameId,
            $playerUnits,
            $playerCities
        );
    }
} 