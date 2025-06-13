<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Game\Query\GetGameViewQuery;
use App\Domain\Game\ValueObject\GameId;
use App\UI\Api\Resource\TurnResource;
use App\UI\Game\ViewModel\GameView;
use App\UI\Turn\ViewModel\TurnView;
use Ecotone\Modelling\QueryBus;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class TurnStateProvider implements ProviderInterface
{
    public function __construct(
        private QueryBus              $queryBus,
        private ObjectMapperInterface $objectMapper,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?TurnResource
    {
        try {
            /** @var GameView $gameView */
            $gameView = $this->queryBus->send(new GetGameViewQuery(new GameId($uriVariables['gameId'])));
        } catch (\RuntimeException $e) {
            throw new NotFoundHttpException("Game with ID {$uriVariables['gameId']} not found");
        }
        $turnView = new TurnView();
        $turnView->gameId = $gameView->id;
        $turnView->activePlayer = $gameView->activePlayer;
        $turnView->currentTurn = $gameView->currentTurn;
        $turnView->turnEndedAt = $gameView->currentTurnAt ?? '';
        return $this->objectMapper->map($turnView, TurnResource::class);
    }
}
