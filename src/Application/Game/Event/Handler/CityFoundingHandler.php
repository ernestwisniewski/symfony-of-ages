<?php

namespace App\Application\Game\Event\Handler;

use App\Application\Unit\Command\DestroyUnitCommand;
use App\Domain\City\Event\CityWasFounded;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Unit\ValueObject\UnitId;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\CommandBus;

final readonly class CityFoundingHandler
{
    public function __construct(
        private CommandBus $commandBus
    )
    {
    }

    #[EventHandler]
    public function onCityWasFounded(CityWasFounded $event): void
    {
        // Po założeniu miasta, zniszcz jednostkę, która je założyła
        $command = new DestroyUnitCommand(
            unitId: new UnitId($event->unitId),
            destroyedAt: Timestamp::now()
        );

        $this->commandBus->send($command);
    }
} 