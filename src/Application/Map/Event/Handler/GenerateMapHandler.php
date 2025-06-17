<?php

namespace App\Application\Map\Event\Handler;

use App\Application\Map\Command\GenerateMapCommand;
use App\Domain\Game\Event\GameWasCreated;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Map\Service\MapGeneratorService;
use App\Domain\Shared\ValueObject\Timestamp;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\CommandBus;

readonly class GenerateMapHandler
{
    public function __construct(private MapGeneratorService $mapGeneratorService, private CommandBus $bus)
    {
    }

    #[EventHandler]
    public function handle(GameWasCreated $event): void
    {
        $this->bus->send(
            new GenerateMapCommand(
                gameId: new GameId($event->gameId),
                tiles: $this->mapGeneratorService->generateTiles(100, 100),
                width: 100,
                height: 100,
                generatedAt: Timestamp::now()
            )
        );
    }
}
