<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Game\Query\GetAllGamesQuery;
use App\Application\Game\Query\GetGameViewQuery;
use App\Domain\Game\ValueObject\GameId;
use App\UI\Api\Resource\GameResource;
use App\UI\Game\ViewModel\GameView;
use Ecotone\Modelling\QueryBus;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class GameStateProvider implements ProviderInterface
{
    public function __construct(
        private QueryBus              $queryBus,
        private ObjectMapperInterface $objectMapper,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $resourceClass = $operation->getClass();

        if ($resourceClass !== GameResource::class) {
            return null;
        }

        if (isset($uriVariables['gameId'])) {
            return $this->provideGame($uriVariables['gameId']);
        }

        return $this->provideGames();
    }

    private function provideGame(string $gameId): ?GameResource
    {
        /** @var GameView $gameView */
        $gameView = $this->queryBus->send(new GetGameViewQuery(new GameId($gameId)));

        return $this->objectMapper->map($gameView, GameResource::class);
    }

    private function provideGames(): array
    {
        /** @var GameView[] $gameViews */
        $gameViews = $this->queryBus->send(new Get());

        return array_map(
            fn(GameView $gameView): GameResource => $this->objectMapper->map($gameView, GameResource::class),
            $gameViews
        );
    }
}
