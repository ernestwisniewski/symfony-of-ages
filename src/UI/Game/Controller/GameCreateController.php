<?php

declare(strict_types=1);

namespace App\UI\Game\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class GameCreateController extends AbstractController
{
    #[Route('/games/create', name: 'app_game_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(): Response
    {
        return $this->render('game/create.html.twig');
    }
}
