<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Api\Resource\GameResource;
use App\Application\Game\Query\GetGameViewQuery;
use App\Domain\Game\ValueObject\GameId;
use App\Infrastructure\Game\ReadModel\Doctrine\GameViewEntity;
use App\Infrastructure\Game\ReadModel\Doctrine\GameViewRepository;
use App\UI\Game\ViewModel\GameView;
use Ecotone\Modelling\QueryBus;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class GameStateProvider implements ProviderInterface
{
    public function __construct(
        private QueryBus              $queryBus,
        private GameViewRepository    $gameViewRepository,
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
        $gameViewEntities = $this->gameViewRepository->findAll();

        return array_map(
            fn(GameViewEntity $entity): GameResource => $this->objectMapper->map(
                $this->objectMapper->map($entity, GameView::class),
                GameResource::class
            ),
            $gameViewEntities
        );
    }
}
