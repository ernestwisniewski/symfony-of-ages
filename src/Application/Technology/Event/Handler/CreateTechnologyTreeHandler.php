<?php

namespace App\Application\Technology\Event\Handler;

use App\Application\Technology\Command\CreateTechnologyCommand;
use App\Domain\Game\Event\PlayerWasJoined;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\CommandBus;

final readonly class CreateTechnologyTreeHandler
{
    public function __construct(
        private CommandBus $commandBus
    )
    {
    }

    #[EventHandler]
    public function handle(PlayerWasJoined $event): void
    {
        $this->commandBus->send(new CreateTechnologyCommand(
            new PlayerId($event->playerId),
            Timestamp::fromString($event->joinedAt),
            0
        ));
    }
}
