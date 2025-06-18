<?php

namespace App\Infrastructure\Unit\ReadModel;

use App\Application\Unit\Query\GetUnitsByGameQuery;
use App\Application\Unit\Query\GetUnitsByPlayerQuery;
use App\Application\Unit\Query\GetUnitViewQuery;
use App\Domain\Unit\Event\UnitWasAttacked;
use App\Domain\Unit\Event\UnitWasCreated;
use App\Domain\Unit\Event\UnitWasDestroyed;
use App\Domain\Unit\Event\UnitWasMoved;
use App\Domain\Unit\Unit;
use App\Domain\Unit\ValueObject\UnitType;
use App\Infrastructure\Exception\EntityNotFoundException;
use App\Infrastructure\Unit\ReadModel\Doctrine\UnitViewEntity;
use App\Infrastructure\Unit\ReadModel\Doctrine\UnitViewRepository;
use App\UI\Unit\ViewModel\UnitView;
use Doctrine\ORM\EntityManagerInterface;
use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

#[Projection("unit_view", Unit::class)]
readonly class UnitViewProjection
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UnitViewRepository     $repository,
        private ObjectMapperInterface  $mapper
    )
    {
    }

    #[QueryHandler]
    public function getUnitView(GetUnitViewQuery $query): UnitView
    {
        $entity = $this->repository->find((string)$query->unitId);
        if (!$entity) {
            throw EntityNotFoundException::unitViewNotFound((string)$query->unitId);
        }
        return $this->mapper->map($entity, UnitView::class);
    }

    #[QueryHandler]
    public function getUnitsByGame(GetUnitsByGameQuery $query): array
    {
        $entities = $this->repository->findByGameId((string)$query->gameId);
        return array_map(
            fn(UnitViewEntity $entity) => $this->mapper->map($entity, UnitView::class),
            $entities
        );
    }

    #[QueryHandler]
    public function getUnitsByPlayer(GetUnitsByPlayerQuery $query): array
    {
        $entities = $this->repository->findByOwner((string)$query->playerId);
        return array_map(
            fn(UnitViewEntity $entity) => $this->mapper->map($entity, UnitView::class),
            $entities
        );
    }

    #[EventHandler]
    public function applyUnitWasCreated(UnitWasCreated $event): void
    {
        $unitType = UnitType::from($event->type);
        $unit = new UnitViewEntity(
            $event->unitId,
            $event->ownerId,
            '', // gameId will be handled separately in infrastructure
            $event->type,
            $event->x,
            $event->y,
            $event->currentHealth,
            $event->maxHealth,
            $unitType->getAttackPower(),
            $unitType->getDefensePower(),
            $unitType->getMovementRange()
        );
        $this->entityManager->persist($unit);
        $this->entityManager->flush();
    }

    #[EventHandler]
    public function applyUnitWasMoved(UnitWasMoved $event): void
    {
        $unit = $this->find($event->unitId);
        $unit->x = $event->toX;
        $unit->y = $event->toY;
        $this->entityManager->flush();
    }

    #[EventHandler]
    public function applyUnitWasAttacked(UnitWasAttacked $event): void
    {
        $unit = $this->find($event->defenderUnitId);
        $unit->currentHealth = $event->remainingHealth;
        if ($event->wasDestroyed) {
            $unit->isDead = true;
        }
        $this->entityManager->flush();
    }

    #[EventHandler]
    public function applyUnitWasDestroyed(UnitWasDestroyed $event): void
    {
        $unit = $this->find($event->unitId);
        $unit->isDead = true;
        $this->entityManager->flush();
    }

    private function find(string $unitId): UnitViewEntity
    {
        $unit = $this->repository->find($unitId);
        if (!$unit) {
            throw EntityNotFoundException::unitViewNotFound($unitId);
        }
        return $unit;
    }
}
