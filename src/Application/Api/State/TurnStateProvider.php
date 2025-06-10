<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Api\Resource\TurnResource;
use App\Application\Game\Query\GetGameViewQuery;
use App\Domain\Game\ValueObject\GameId;
use App\UI\Game\ViewModel\GameView;
use App\UI\Turn\ViewModel\TurnView;
use Ecotone\Modelling\QueryBus;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

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
        /** @var GameView $gameView */
        $gameView = $this->queryBus->send(new GetGameViewQuery(new GameId($uriVariables['gameId'])));

        $turnView = new TurnView();
        $turnView->gameId = $gameView->id;
        $turnView->activePlayer = $gameView->activePlayer;
        $turnView->currentTurn = $gameView->currentTurn;
        $turnView->turnEndedAt = $gameView->currentTurnAt ?? '';

        return $this->objectMapper->map($turnView, TurnResource::class);
    }
}
