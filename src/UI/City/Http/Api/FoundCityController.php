<?php

namespace App\UI\City\Http\Api;

use App\Application\City\Command\FoundCityCommand;
use App\Domain\City\ValueObject\CityId;
use App\Domain\City\ValueObject\CityName;
use App\Domain\City\ValueObject\Position;
use App\Domain\Player\ValueObject\PlayerId;
use Ecotone\Modelling\CommandBus;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
readonly class FoundCityController
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    #[Route('/api/game/{gameId}/{playerId}/found_city', name: 'app_game_found_city', methods: ['GET'])]
    public function __invoke(string $gameId, string $playerId): Response
    {
        $cities = ['Warsaw', 'Berlin', 'Amsterdam', 'Madrid', 'Tokio'];

        shuffle($cities);

        $this->commandBus->send(
            new FoundCityCommand(
                new CityId(UuidV4::fromString($gameId)->toString()),
                new PlayerId($playerId),
                new CityName($cities[0]),
                new Position(10, 20)
            )
        );

        return new JsonResponse(['success' => true]);
    }
}
