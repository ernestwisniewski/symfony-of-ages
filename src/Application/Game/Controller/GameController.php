<?php

namespace App\Application\Game\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * MapController handles the main game interface and map data API
 *
 * Provides routes for displaying the game interface and serving map data
 * to the frontend JavaScript application. Manages the hexagonal map
 * configuration and data transformation for client consumption.
 */
class GameController extends AbstractController
{
    /**
     * Main game page route
     *
     * Renders the game interface. Map configuration and data are now
     * loaded dynamically via the API endpoint.
     *
     * @return Response The rendered game template
     */
    #[Route('/game', name: 'app_game')]
    public function index(): Response
    {
        return $this->render('game/index.html.twig');
    }
}
