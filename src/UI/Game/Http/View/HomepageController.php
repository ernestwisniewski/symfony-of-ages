<?php

namespace App\UI\Game\Http\View;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * MapController handles the main game interface and map data API
 *
 * Provides routes for displaying the game interface and serving map data
 * to the frontend JavaScript application. Manages the hexagonal map
 * configuration and data transformation for client consumption.
 */
#[AsController]
readonly class HomepageController
{
    public function __construct(private Environment $twig)
    {
    }

    /**
     * Main game page route
     *
     * Renders the game interface. Map configuration and data are now
     * loaded dynamically via the API endpoint.
     *
     * @return Response The rendered game template
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route('/game', name: 'app_game', methods: ['GET'])]
    public function index(): Response
    {
        return new Response($this->twig->render('game/index.html.twig'));
    }
}
