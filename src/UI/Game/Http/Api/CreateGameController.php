<?php

namespace App\UI\Game\Http\Api;

use App\Application\Game\Command\CreateGameCommand;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameName;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use DateTimeImmutable;
use Ecotone\Modelling\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

#[AsController]
readonly class CreateGameController
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    #[Route('/api/new_game', name: 'app_game_create', methods: ['GET'])]
    public function __invoke(): Response
    {
        $this->commandBus->send(
            new CreateGameCommand(
                new GameId(Uuid::v4()->toRfc4122()),
                new PlayerId(Uuid::v4()->toRfc4122()),
                new GameName('Test Game'),
                new Timestamp(new DateTimeImmutable())
            )
        );

        return new JsonResponse(['success' => true]);
    }
}
