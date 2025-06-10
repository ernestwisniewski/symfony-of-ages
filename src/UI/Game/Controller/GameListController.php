<?php

declare(strict_types=1);

namespace App\UI\Game\Controller;

use App\Infrastructure\Game\ReadModel\Doctrine\GameViewEntity;
use App\Infrastructure\Game\ReadModel\Doctrine\GameViewRepository;
use App\UI\Game\ViewModel\GameView;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class GameListController
{
    public function __construct(
        private GameViewRepository    $gameViewRepository,
        private Security              $security,
        private ObjectMapperInterface $objectMapper
    )
    {

    }

    #[Route('/games', name: 'app_games')]
    public function index(): Response
    {
        $games = array_map(function (GameViewEntity $gameViewEntity) {
            return $this->objectMapper->map($gameViewEntity, GameView::class);
        }, $this->gameViewRepository->findByUser($this->security->getUser()));

        return $this->render('game/index.html.twig', ['games' => $games]);
    }
}
