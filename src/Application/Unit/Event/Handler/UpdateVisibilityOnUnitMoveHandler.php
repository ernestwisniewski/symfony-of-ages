<?php

namespace App\Application\Unit\Event\Handler;

use App\Application\City\Query\GetCitiesByPlayerQuery;
use App\Application\Unit\Query\GetUnitsByPlayerQuery;
use App\Application\Visibility\Service\VisibilityApplicationService;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Unit\Event\UnitWasMoved;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\QueryBus;

final readonly class UpdateVisibilityOnUnitMoveHandler
{
    public function __construct(
        private QueryBus                     $queryBus,
        private VisibilityApplicationService $visibilityService
    )
    {
    }

    #[EventHandler]
    public function handle(UnitWasMoved $event): void
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
