<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Map\Query\GetMapViewQuery;
use App\Domain\Game\ValueObject\GameId;
use App\UI\Api\Resource\MapResource;
use App\UI\Map\ViewModel\MapView;
use Ecotone\Modelling\QueryBus;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class MapStateProvider implements ProviderInterface
{
    public function __construct(
        private QueryBus              $queryBus,
        private ObjectMapperInterface $objectMapper,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?MapResource
    {
        $gameId = $uriVariables['gameId'] ?? null;
        try {
            /** @var MapView $mapView */
            $mapView = $this->queryBus->send(new GetMapViewQuery(new GameId($gameId)));
        } catch (\RuntimeException $e) {
            throw new NotFoundHttpException("Map for game $gameId not found");
        }
        return $this->objectMapper->map($mapView, MapResource::class);
    }
}
