<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use App\Application\Visibility\Query\GetGameVisibilityQuery;
use App\Application\Visibility\Query\GetPlayerVisibilityQuery;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\UI\Api\Resource\VisibilityResource;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\QueryBus;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class VisibilityStateProvider
{
    public function __construct(
        private QueryBus              $queryBus,
        private ObjectMapperInterface $objectMapper,
    )
    {
    }

    #[QueryHandler]
    public function provide(Operation $operation, array $uriVariables = []): object|array|null
    {
        return match ($operation->getName()) {
            'get_player_visibility' => $this->getPlayerVisibility($uriVariables['playerId']),
            'get_game_visibility' => $this->getGameVisibility($uriVariables['gameId']),
            default => null,
        };
    }

    private function getPlayerVisibility(string $playerId): array
    {
        $query = new GetPlayerVisibilityQuery(
            new PlayerId($playerId)
        );

        $visibility = $this->queryBus->send($query);

        return array_map(
            fn($item) => $this->objectMapper->map($item, VisibilityResource::class),
            $visibility
        );
    }

    private function getGameVisibility(string $gameId): array
    {
        $query = new GetGameVisibilityQuery(new GameId($gameId));
        $visibility = $this->queryBus->send($query);

        return array_map(
            fn($item) => $this->objectMapper->map($item, VisibilityResource::class),
            $visibility
        );
    }
}
