<?php

namespace App\Application\City\Event\Handler;

use App\Application\Unit\Query\GetUnitsByGameQuery;
use App\Application\City\Query\GetCitiesByGameQuery;
use App\Application\Visibility\Service\VisibilityApplicationService;
use App\Domain\City\Event\CityWasFounded;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\QueryBus;

final readonly class UpdateVisibilityOnCityFoundedHandler
{
    public function __construct(
        private QueryBus $queryBus,
        private VisibilityApplicationService $visibilityService
    ) {
    }

    #[EventHandler]
    public function handle(CityWasFounded $event): void
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