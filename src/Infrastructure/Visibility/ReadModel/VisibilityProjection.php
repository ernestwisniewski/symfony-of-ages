<?php

namespace App\Infrastructure\Visibility\ReadModel;

use App\Application\Visibility\Query\GetGameVisibilityQuery;
use App\Application\Visibility\Query\GetPlayerVisibilityQuery;
use App\Domain\Visibility\Event\VisibilityRevealed;
use App\Domain\Visibility\Event\VisibilityUpdated;
use App\Domain\Visibility\PlayerVisibility;
use App\Infrastructure\Visibility\ReadModel\Doctrine\PlayerVisibilityEntity;
use App\Infrastructure\Visibility\ReadModel\Doctrine\PlayerVisibilityRepository;
use App\UI\Visibility\ViewModel\PlayerVisibilityView;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

#[Projection("visibility_projection", PlayerVisibility::class)]
final readonly class VisibilityProjection
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PlayerVisibilityRepository $repository,
        private ObjectMapperInterface $objectMapper
    ) {
    }

    #[QueryHandler]
    public function getPlayerVisibility(GetPlayerVisibilityQuery $query): array
    {
        $entities = $this->repository->findByPlayerAndGame(
            (string)$query->playerId,
            (string)$query->gameId
        );
        
        return array_map(
            fn(PlayerVisibilityEntity $entity) => new PlayerVisibilityView(
                $entity->playerId,
                $entity->gameId,
                $entity->x,
                $entity->y,
                $entity->state,
                $entity->updatedAt->format('Y-m-d\TH:i:s\Z')
            ),
            $entities
        );
    }

    #[QueryHandler]
    public function getGameVisibility(GetGameVisibilityQuery $query): array
    {
        $entities = $this->repository->findByGameId((string)$query->gameId);
        
        return array_map(
            fn(PlayerVisibilityEntity $entity) => new PlayerVisibilityView(
                $entity->playerId,
                $entity->gameId,
                $entity->x,
                $entity->y,
                $entity->state,
                $entity->updatedAt->format('Y-m-d\TH:i:s\Z')
            ),
            $entities
        );
    }

    #[EventHandler]
    public function applyVisibilityUpdated(VisibilityUpdated $event): void
    {
        $visibility = new PlayerVisibilityEntity(
            $event->playerId,
            $event->gameId,
            $event->x,
            $event->y,
            $event->state,
            new DateTimeImmutable($event->updatedAt)
        );
        
        $this->entityManager->persist($visibility);
        $this->entityManager->flush();
    }

    #[EventHandler]
    public function applyVisibilityRevealed(VisibilityRevealed $event): void
    {
        $visibility = new PlayerVisibilityEntity(
            $event->playerId,
            $event->gameId,
            $event->x,
            $event->y,
            'discovered',
            new DateTimeImmutable($event->revealedAt)
        );
        
        $this->entityManager->persist($visibility);
        $this->entityManager->flush();
    }
} 