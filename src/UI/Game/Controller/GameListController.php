<?php
declare(strict_types=1);

namespace App\UI\Game\Controller;

use App\Application\Game\Query\GetAllGamesQuery;
use App\Application\Game\Query\GetUserGamesQuery;
use App\Domain\Shared\ValueObject\UserId;
use Ecotone\Modelling\QueryBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    public function all(): Response
    {
        $games = $this->queryBus->send(new GetAllGamesQuery());
        return $this->render('game/game_list.html.twig', ['games' => $games]);
    }

    #[isGranted('ROLE_USER')]
    #[Route('/games', name: 'app_user_games', methods: ['GET'])]
    public function user(): Response
    {
        $games = $this->queryBus->send(new GetUserGamesQuery(
                new UserId($this->security->getUser()->getId()))
        );
        return $this->render('game/game_list.html.twig', ['games' => $games]);
    }
}
