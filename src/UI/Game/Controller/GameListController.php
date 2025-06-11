<?php

declare(strict_types=1);

namespace App\UI\Game\Controller;

use App\Application\Game\Query\GetUserGamesQuery;
use App\Domain\Shared\ValueObject\UserId;
use App\UI\Game\ViewModel\GameView;
use Ecotone\Modelling\QueryBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class GameListController extends AbstractController
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly Security $security,
    )
    {

    }

    #[Route('/games', name: 'app_games', methods: ['GET'])]
    public function index(): Response
    {
        /** @var GameView[] $games */
        $games = $this->queryBus->send(new GetUserGamesQuery(
                new UserId($this->security->getUser()->getId()))
        );

        return $this->render('account/game/all_games.html.twig', ['games' => $games]);
    }
}
