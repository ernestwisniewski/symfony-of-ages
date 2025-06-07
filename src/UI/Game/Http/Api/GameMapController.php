<?php

namespace App\UI\Game\Http\Api;

use App\Application\Map\Query\GetMapTilesQuery;
use App\Domain\Game\ValueObject\GameId;
use Ecotone\Modelling\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
readonly class GameMapController
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    #[Route('/api/game/{gameId}/map', name: 'app_game_map', methods: ['GET'])]
    public function __invoke(string $gameId): Response
    {
        $mapView = $this->queryBus->send(new GetMapTilesQuery(new GameId($gameId)));

        return new JsonResponse($mapView);
    }
}
