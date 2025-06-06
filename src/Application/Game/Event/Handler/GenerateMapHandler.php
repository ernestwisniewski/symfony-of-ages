<?php

namespace App\Application\Game\Event\Handler;

use App\Application\Map\Command\GenerateMapCommand;
use App\Domain\Game\Event\GameWasCreated;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\CommandBus;

class GenerateMapHandler
{
    public function __construct(private CommandBus $bus)
    {

    }

    #[EventHandler]
    public function whenGameWasCreated(GameWasCreated $event): void
    {
        $this->bus->send(
            new GenerateMapCommand(
                gameId: $event->gameId,
                width: 10,
                height: 10,
            )
        );

    }
}
