<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Game\Query\GetAllGamesQuery;
use App\Application\Game\Query\GetGameViewQuery;
use App\Application\Game\Query\GetUserGamesQuery;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Shared\ValueObject\UserId;
use App\UI\Api\Resource\GameResource;
use App\UI\Game\ViewModel\GameView;
use Ecotone\Modelling\QueryBus;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class GameStateProvider implements ProviderInterface
{
    public function __construct(
        private QueryBus              $queryBus,
        private ObjectMapperInterface $objectMapper,
        private Security              $security,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $uriTemplate = $operation->getUriTemplate();
        return match ($uriTemplate) {
            '/games/{gameId}' => $this->getGame($uriVariables['gameId']),
            '/games' => $this->getAllGames(),
            '/my-games' => $this->getUserGames(),
            default => null,
        };
    }

    private function getGame(string $gameId): ?GameResource
    {
        try {
            $gameView = $this->queryBus->send(new GetGameViewQuery(new GameId($gameId)));
        } catch (RuntimeException $e) {
            throw new NotFoundHttpException("Game with ID $gameId not found");
        }
        return $this->objectMapper->map($gameView, GameResource::class);
    }

    private function getAllGames(): array
    {
        $gameViews = $this->queryBus->send(new GetAllGamesQuery());
        return array_map(
            fn(GameView $gameView): GameResource => $this->objectMapper->map($gameView, GameResource::class),
            $gameViews
        );
    }

    private function getUserGames(): array
    {
        $gameViews = $this->queryBus->send(new GetUserGamesQuery(new UserId($this->security->getUser()->getId())));
        return array_map(
            fn(GameView $gameView): GameResource => $this->objectMapper->map($gameView, GameResource::class),
            $gameViews
        );
    }
}
