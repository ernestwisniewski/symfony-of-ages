<?php

namespace App\UI\Game\Http\Api;

use App\Application\Game\Command\StartGameCommand;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Shared\ValueObject\Timestamp;
use DateTimeImmutable;
use Ecotone\Modelling\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
readonly class StartGameController
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    #[Route('/api/game/{gameId}/start', name: 'app_game_start', methods: ['GET'])]
    public function __invoke(string $gameId): Response
    {
        $this->commandBus->send(
            new StartGameCommand(
                new GameId($gameId),
                new Timestamp(new DateTimeImmutable())
            )
        );

        return new JsonResponse(['success' => true]);
    }
}
