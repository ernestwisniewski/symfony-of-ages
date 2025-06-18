<?php

namespace App\Infrastructure\City\ReadModel;

use App\Application\City\Query\GetCitiesByGameQuery;
use App\Application\City\Query\GetCitiesByPlayerQuery;
use App\Application\City\Query\GetCityViewQuery;
use App\Domain\City\City;
use App\Domain\City\Event\CityWasFounded;
use App\Infrastructure\City\ReadModel\Doctrine\CityViewEntity;
use App\Infrastructure\City\ReadModel\Doctrine\CityViewRepository;
use App\Infrastructure\Exception\EntityNotFoundException;
use App\UI\City\ViewModel\CityView;
use Doctrine\ORM\EntityManagerInterface;
use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

#[Projection("city_view", City::class)]
readonly class CityViewProjection
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CityViewRepository     $repository,
        private ObjectMapperInterface  $mapper
    )
    {
    }

    #[QueryHandler]
    public function getCityView(GetCityViewQuery $query): CityView
    {
        $entity = $this->repository->find((string)$query->cityId);
        if (!$entity) {
            throw EntityNotFoundException::cityViewNotFound((string)$query->cityId);
        }
        return $this->mapper->map($entity, CityView::class);
    }

    #[QueryHandler]
    public function getCitiesByGame(GetCitiesByGameQuery $query): array
    {
        $entities = $this->repository->findByGameId((string)$query->gameId);
        return array_map(
            fn(CityViewEntity $entity) => $this->mapper->map($entity, CityView::class),
            $entities
        );
    }

    #[QueryHandler]
    public function getCitiesByPlayer(GetCitiesByPlayerQuery $query): array
    {
        $entities = $this->repository->findByOwner((string)$query->playerId);
        return array_map(
            fn(CityViewEntity $entity) => $this->mapper->map($entity, CityView::class),
            $entities
        );
    }

    #[EventHandler]
    public function applyCityWasFounded(CityWasFounded $event): void
    {
        $city = new CityViewEntity(
            $event->cityId,
            $event->ownerId,
            '', // gameId will be handled separately in infrastructure
            $event->name,
            $event->x,
            $event->y
        );
        $this->entityManager->persist($city);
        $this->entityManager->flush();
    }
}
