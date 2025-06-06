<?php

namespace App\Infrastructure\Map\ReadModel;

use App\Application\Map\Query\GetMapTilesQuery;
use App\Domain\Game\Event\MapWasGenerated;
use App\Domain\Game\Game;
use App\Infrastructure\Map\ReadModel\Doctrine\MapTileViewEntity;
use App\Infrastructure\Map\ReadModel\Doctrine\MapTileViewRepository;
use App\UI\Map\ViewModel\MapTileView;
use Doctrine\ORM\EntityManagerInterface;
use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

#[Projection("map_view", Game::class)]
readonly class MapProjection
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MapTileViewRepository  $mapTileViewRepository,
        private ObjectMapperInterface  $objectMapper
    )
    {
    }

    #[QueryHandler]
    public function getMapTiles(GetMapTilesQuery $query): array
    {
        $map = [];
        foreach ($this->find($query->gameId) as $mapTile) {
            $map[] = $this->objectMapper->map($mapTile, MapTileView::class);
        }

        return $map;
    }

    #[EventHandler]
    public function onMapGenerated(MapWasGenerated $event): void
    {

        foreach (json_decode($event->tiles, true) as $tileData) {
            $tile = new MapTileViewEntity($event->gameId, $tileData['x'], $tileData['y'], $tileData['terrain']);
            $this->entityManager->persist($tile);
        }

        $this->entityManager->flush();
    }

    private function find(string $gameId): array
    {
        $mapView = $this->mapTileViewRepository->findByGameId($gameId);

        if (!$mapView) {
            throw new \RuntimeException("MapViewEntity for game ID $gameId not found");
        }

        return $mapView;
    }
}
