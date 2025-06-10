<?php

namespace App\Infrastructure\Map\ReadModel;

use App\Application\Map\Query\FindMapByGame;
use App\Application\Map\Query\GetMapTilesQuery;
use App\Application\Map\Query\GetMapViewQuery;
use App\Domain\Game\Event\MapWasGenerated;
use App\Domain\Game\Game;
use App\Infrastructure\Map\ReadModel\Doctrine\MapTileViewEntity;
use App\Infrastructure\Map\ReadModel\Doctrine\MapTileViewRepository;
use App\Infrastructure\Map\ReadModel\Doctrine\MapViewEntity;
use App\Infrastructure\Map\ReadModel\Doctrine\MapViewRepository;
use App\UI\Map\ViewModel\MapTileView;
use App\UI\Map\ViewModel\MapView;
use Doctrine\ORM\EntityManagerInterface;
use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use RuntimeException;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

#[Projection("map_view", Game::class)]
readonly class MapProjection
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MapTileViewRepository  $mapTileViewRepository,
        private MapViewRepository      $mapViewRepository,
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

    #[QueryHandler]
    public function getMapView(GetMapViewQuery $query): MapView
    {
        $mapViewEntity = $this->mapViewRepository->find((string)$query->gameId);

        if (!$mapViewEntity) {
            throw new RuntimeException("MapView for game ID {$query->gameId} not found");
        }

        return $this->objectMapper->map($mapViewEntity, MapView::class);
    }

    #[QueryHandler]
    public function findMapByGame(FindMapByGame $query): ?MapView
    {
        $mapViewEntity = $this->mapViewRepository->find($query->gameId);

        if (!$mapViewEntity) {
            return null;
        }

        return $this->objectMapper->map($mapViewEntity, MapView::class);
    }

    #[EventHandler]
    public function onMapGenerated(MapWasGenerated $event): void
    {
        // Create overall map view
        $mapView = new MapViewEntity(
            $event->gameId,
            $event->width,
            $event->height,
            json_decode($event->tiles, true),
            $event->generatedAt
        );
        $this->entityManager->persist($mapView);

        // Create individual tile views
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
            throw new RuntimeException("MapViewEntity for game ID $gameId not found");
        }

        return $mapView;
    }
}
