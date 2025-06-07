<?php

namespace App\UI\Game\Http\Api;

use App\Application\Game\Command\EndTurnCommand;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use DateTimeImmutable;
use Ecotone\Modelling\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
readonly class EndTurnController
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    #[Route('/api/game/{gameId}/{playerId}/end_turn', name: 'app_game_end_turn', methods: ['GET'])]
    public function __invoke(string $gameId, string $playerId): Response
    {
        $this->commandBus->send(
            new EndTurnCommand(
                new GameId($gameId),
                new PlayerId($playerId),
                new Timestamp(new DateTimeImmutable())
            )
        );

        return new JsonResponse(['success' => true]);
    }
}
