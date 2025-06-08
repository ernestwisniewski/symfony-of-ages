<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Api\Resource\MapResource;
use App\Application\Map\Command\GenerateMapCommand;
use App\Domain\Map\Service\MapGeneratorService;
use App\Domain\Shared\ValueObject\Timestamp;
use Ecotone\Modelling\CommandBus;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class MapStateProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBus          $commandBus,
        private MapStateProvider    $mapStateProvider,
        private MapGeneratorService $mapGeneratorService,
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MapResource|null
    {
        if (!$data instanceof MapResource) {
            throw new BadRequestHttpException('Invalid data type');
        }

        $gameId = $uriVariables['gameId'] ?? null;
        if (!$gameId) {
            throw new BadRequestHttpException('Game ID is required');
        }


        $tiles = $this->mapGeneratorService->generateTiles($data->mapWidth, $data->mapHeight);

        $command = new GenerateMapCommand(
            gameId: $gameId,
            tiles: $tiles,
            width: $data->mapWidth,
            height: $data->mapHeight,
            generatedAt: Timestamp::now()
        );

        $this->commandBus->send($command);

        // CQRS: Commands should not return data, only execute actions
        return null;
    }
}
