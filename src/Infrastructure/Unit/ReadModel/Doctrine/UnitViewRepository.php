<?php

namespace App\Infrastructure\Unit\ReadModel\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UnitViewEntity>
 */
class UnitViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnitViewEntity::class);
    }

    public function findByGameId(string $gameId): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.gameId = :gameId')
            ->andWhere('u.isDead = false')
            ->setParameter('gameId', $gameId)
            ->getQuery()
            ->getResult();
    }

    public function findByOwner(string $ownerId): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.ownerId = :ownerId')
            ->andWhere('u.isDead = false')
            ->setParameter('ownerId', $ownerId)
            ->getQuery()
            ->getResult();
    }
} 