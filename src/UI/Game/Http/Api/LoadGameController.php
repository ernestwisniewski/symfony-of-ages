<?php

namespace App\UI\Game\Http\Api;


use App\Application\Game\Query\GetGameViewQuery;
use App\Domain\Game\ValueObject\GameId;
use Ecotone\Modelling\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
readonly class LoadGameController
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    #[Route('/api/game/{gameId}', name: 'app_game_load', methods: ['GET'])]
    public function __invoke(string $gameId): Response
    {
        $viewModel = $this->queryBus->send(new GetGameViewQuery(new GameId($gameId)));

        return new JsonResponse($viewModel);
    }
}
