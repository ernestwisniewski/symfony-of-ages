<?php

namespace App\Infrastructure\Player\ReadModel\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PlayerUserMappingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerUserMappingEntity::class);
    }

    public function findByGameId(string $gameId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.gameId = :gameId')
            ->setParameter('gameId', $gameId)
            ->getQuery()
            ->getResult();
    }

    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function findByPlayerId(string $playerId): ?PlayerUserMappingEntity
    {
        return $this->find($playerId);
    }

    public function findUserIdsByGameId(string $gameId): array
    {
        $mappings = $this->findByGameId($gameId);
        return array_map(fn(PlayerUserMappingEntity $mapping) => $mapping->userId, $mappings);
    }
}
