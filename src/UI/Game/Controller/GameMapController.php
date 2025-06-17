<?php
declare(strict_types=1);

namespace App\UI\Game\Controller;

use App\Application\City\Query\GetCitiesByGameQuery;
use App\Application\Game\Query\GetGameViewQuery;
use App\Application\Map\Query\GetMapViewQuery;
use App\Application\Unit\Query\GetUnitsByGameQuery;
use App\Domain\Game\ValueObject\GameId;
use Ecotone\Modelling\QueryBus;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class GameMapController extends AbstractController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    )
    {
    }

    #[Route('/game/{gameId}/map', name: 'app_game_map', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(string $gameId): Response
    {
        try {
            $gameView = $this->queryBus->send(new GetGameViewQuery(new GameId($gameId)));
            $mapView = $this->queryBus->send(new GetMapViewQuery(new GameId($gameId)));
            $unitsView = $this->queryBus->send(new GetUnitsByGameQuery(new GameId($gameId)));
            $citiesView = $this->queryBus->send(new GetCitiesByGameQuery(new GameId($gameId)));
        } catch (RuntimeException $e) {
            throw new NotFoundHttpException("Game with ID $gameId not found");
        }
        return $this->render('game/game.html.twig', [
            'game' => $gameView,
            'map' => $mapView,
            'units' => $unitsView,
            'cities' => $citiesView,
        ]);
    }
}
