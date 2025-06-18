<?php

namespace App\Application\City\Event\Handler;

use App\Application\City\Query\GetCitiesByPlayerQuery;
use App\Application\Unit\Query\GetUnitsByPlayerQuery;
use App\Application\Visibility\Service\VisibilityApplicationService;
use App\Domain\City\Event\CityWasFounded;
use App\Domain\Player\ValueObject\PlayerId;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\QueryBus;

final readonly class UpdateVisibilityOnCityFoundedHandler
{
    public function __construct(
        private QueryBus                     $queryBus,
        private VisibilityApplicationService $visibilityService
    )
    {
    }

    #[EventHandler]
    public function handle(CityWasFounded $event): void
    {
        $playerId = new PlayerId($event->ownerId);

        $units = $this->queryBus->send(new GetUnitsByPlayerQuery($playerId));
        $cities = $this->queryBus->send(new GetCitiesByPlayerQuery($playerId));

        $this->visibilityService->updatePlayerVisibility(
            $playerId,
            $units,
            $cities
        );
    }
}
