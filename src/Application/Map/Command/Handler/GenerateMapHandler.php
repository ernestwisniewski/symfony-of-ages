<?php

namespace App\Application\Map\Command\Handler;

use App\Application\Game\Command\CreateGameCommand;
use App\Application\Map\Command\GenerateMapCommand;
use App\Application\Map\Service\MapGeneratorService;
use App\Domain\Map\Event\MapWasGenerated;
use Ecotone\Modelling\Attribute\CommandHandler;

class GenerateMapHandler
{
    public function __construct(private MapGeneratorService $mapGeneratorService)
    {

    }
    #[CommandHandler]
    public function handleMapCreate(GenerateMapCommand $command): array
    {
        $tiles = $this->mapGeneratorService->generateTiles(10, 10);

        return [
            new MapWasGenerated(
                gameId: $command->gameId,
                width: 10,
                height: 10,
                tiles: $tiles,
            )
        ];
    }
}
