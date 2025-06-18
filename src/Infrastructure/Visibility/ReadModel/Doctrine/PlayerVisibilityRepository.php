<?php

namespace App\Infrastructure\Visibility\ReadModel\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PlayerVisibilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerVisibilityEntity::class);
    }

    public function findByPlayerAndGame(string $playerId, string $gameId): array
    {
        return $this->createQueryBuilder('pv')
            ->where('pv.playerId = :playerId')
            ->andWhere('pv.gameId = :gameId')
            ->setParameter('playerId', $playerId)
            ->setParameter('gameId', $gameId)
            ->getQuery()
            ->getResult();
    }

    public function findByGameId(string $gameId): array
    {
        return $this->createQueryBuilder('pv')
            ->where('pv.gameId = :gameId')
            ->setParameter('gameId', $gameId)
            ->getQuery()
            ->getResult();
    }

    public function findActiveByPlayerAndGame(string $playerId, string $gameId): array
    {
        return $this->createQueryBuilder('pv')
            ->where('pv.playerId = :playerId')
            ->andWhere('pv.gameId = :gameId')
            ->andWhere('pv.state = :state')
            ->setParameter('playerId', $playerId)
            ->setParameter('gameId', $gameId)
            ->setParameter('state', 'active')
            ->getQuery()
            ->getResult();
    }
} 