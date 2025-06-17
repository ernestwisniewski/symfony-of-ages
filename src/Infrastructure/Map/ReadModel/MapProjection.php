<?php

namespace App\Infrastructure\Map\ReadModel;

use App\Application\Map\Query\FindMapByGame;
use App\Application\Map\Query\GetMapTilesQuery;
use App\Application\Map\Query\GetMapViewQuery;
use App\Domain\Game\Event\MapWasGenerated;
use App\Domain\Game\Game;
use App\Infrastructure\Map\ReadModel\Doctrine\MapTileViewRepository;
use App\Infrastructure\Map\ReadModel\Doctrine\MapViewEntity;
use App\Infrastructure\Map\ReadModel\Doctrine\MapViewRepository;
use App\UI\Map\ViewModel\MapTileView;
use App\UI\Map\ViewModel\MapView;
use Doctrine\ORM\EntityManagerInterface;
use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Exception;
use RuntimeException;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Uid\Uuid;

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
        $tiles = json_decode($event->tiles, true);
        $mapView = new MapViewEntity(
            $event->gameId,
            $event->width,
            $event->height,
            $tiles,
            $event->generatedAt
        );
        $this->entityManager->persist($mapView);
        $this->entityManager->flush();
        $conn = $this->entityManager->getConnection();
        $chunkSize = 1000;
        $sqlPrefix = <<<SQL
            INSERT INTO map_tile_view (id, game_id, x, y, terrain, is_occupied)
            VALUES
        SQL;
        $conn->beginTransaction();
        try {
            foreach (array_chunk($tiles, $chunkSize) as $chunk) {
                $values = [];
                $params = [];
                $paramIndex = 0;
                foreach ($chunk as $t) {
                    $values[] = "( :u{$paramIndex}, :g{$paramIndex}, :x{$paramIndex}, :y{$paramIndex}, :tr{$paramIndex}, :o{$paramIndex} )";
                    $params["u{$paramIndex}"] = Uuid::v4()->toRfc4122();
                    $params["g{$paramIndex}"] = $event->gameId;
                    $params["x{$paramIndex}"] = $t['x'];
                    $params["y{$paramIndex}"] = $t['y'];
                    $params["tr{$paramIndex}"] = $t['terrain'];
                    $params["o{$paramIndex}"] = 'false';
                    ++$paramIndex;
                }
                $sql = $sqlPrefix . implode(',', $values);
                $conn->executeStatement($sql, $params);
            }
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
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
