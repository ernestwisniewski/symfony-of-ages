<?php

namespace App\UI\Game\Http\Api;

use App\Application\Game\Command\JoinGameCommand;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use Ecotone\Modelling\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

#[AsController]
readonly class JoinGameController
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    #[Route('/api/game/{gameId}/join', name: 'app_game_join', methods: ['GET'])]
    public function __invoke($gameId): Response
    {
        $this->commandBus->send(
            new JoinGameCommand(
                new GameId($gameId),
                new PlayerId(Uuid::v4()->toRfc4122()),
            )
        );

        return new JsonResponse(['success' => true]);
    }
}
