<?php

namespace App\Infrastructure\Player\ReadModel;

use App\Application\Player\Query\GetUserIdsByGameQuery;
use App\Domain\Game\Event\PlayerWasJoined;
use App\Domain\Player\Event\PlayerWasCreated;
use App\Infrastructure\Player\ReadModel\Doctrine\PlayerUserMappingEntity;
use App\Infrastructure\Player\ReadModel\Doctrine\PlayerUserMappingRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Symfony\Component\Uid\UuidV4;

#[Projection("player_user_mapping", ["App\Domain\Player\Player", "App\Domain\Game\Game"])]
readonly class PlayerUserMappingProjection
{
    public function __construct(
        private EntityManagerInterface      $entityManager,
        private PlayerUserMappingRepository $repository
    )
    {
    }

    #[QueryHandler]
    public function getUserIdsByGame(GetUserIdsByGameQuery $query): array
    {
        return $this->repository->findUserIdsByGameId((string)$query->gameId);
    }

    #[EventHandler]
    public function applyPlayerWasCreated(PlayerWasCreated $event): void
    {
        $mapping = new PlayerUserMappingEntity(
            id: UuidV4::v4()->toRfc4122(),
            playerId: $event->playerId,
            userId: $event->userId,
            gameId: $event->gameId,
            createdAt: new DateTimeImmutable()
        );

        $this->entityManager->persist($mapping);
        $this->entityManager->flush();
    }

    #[EventHandler]
    public function applyPlayerWasJoined(PlayerWasJoined $event): void
    {
        $mapping = new PlayerUserMappingEntity(
            id: UuidV4::v4()->toRfc4122(),
            playerId: $event->playerId,
            userId: $event->userId,
            gameId: $event->gameId,
            createdAt: new DateTimeImmutable($event->joinedAt)
        );

        $this->entityManager->persist($mapping);
        $this->entityManager->flush();
    }
}
