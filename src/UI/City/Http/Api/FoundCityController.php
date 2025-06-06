<?php

namespace App\UI\City\Http\Api;

use App\Application\City\Command\FoundCityCommand;
use App\Domain\City\ValueObject\CityId;
use App\Domain\City\ValueObject\CityName;
use App\Domain\City\ValueObject\Position;
use App\Domain\Map\ValueObject\TerrainType;
use App\Domain\Player\ValueObject\PlayerId;
use Ecotone\Modelling\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

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

        // In a real application, you would:
        // 1. Get map tiles for the game to find suitable terrain
        // 2. Get existing cities to check for position conflicts
        // 3. Let user choose position via POST with coordinates
        
        // For demo purposes, using plains terrain at random position
        $position = new Position(rand(5, 15), rand(5, 15));
        $terrain = TerrainType::PLAINS; // Safe terrain for city founding
        $existingCityPositions = []; // TODO: Get from repository

        $this->commandBus->send(
            new FoundCityCommand(
                new CityId(Uuid::v4()->toRfc4122()),
                new PlayerId($playerId),
                new CityName($cities[0]),
                $position,
                $terrain,
                $existingCityPositions
            )
        );

        return new JsonResponse(['success' => true]);
    }
}
