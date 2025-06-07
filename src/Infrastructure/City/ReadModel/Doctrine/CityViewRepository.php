<?php

namespace App\Infrastructure\City\ReadModel\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CityViewEntity>
 */
class CityViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CityViewEntity::class);
    }

    public function findByGameId(string $gameId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.gameId = :gameId')
            ->setParameter('gameId', $gameId)
            ->getQuery()
            ->getResult();
    }

    public function findByOwner(string $ownerId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId)
            ->getQuery()
            ->getResult();
    }
}
