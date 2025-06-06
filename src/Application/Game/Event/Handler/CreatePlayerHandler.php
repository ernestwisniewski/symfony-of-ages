<?php

namespace App\Application\Game\Event\Handler;

use App\Application\Player\Command\CreatePlayerCommand;
use App\Domain\Game\Event\GameWasCreated;
use App\Domain\Game\Event\PlayerWasJoined;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\CommandBus;

readonly class CreatePlayerHandler
{
    public function __construct(private CommandBus $bus)
    {
    }

    #[EventHandler]
    public function handle(PlayerWasJoined|GameWasCreated $event): void
    {
        $this->bus->send(new CreatePlayerCommand(
            new PlayerId($event->playerId),
            new GameId($event->gameId)
        ));
    }
}
