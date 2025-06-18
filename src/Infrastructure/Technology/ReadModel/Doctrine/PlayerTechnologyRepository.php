<?php

namespace App\Infrastructure\Technology\ReadModel\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PlayerTechnologyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerTechnologyEntity::class);
    }

    public function findByPlayerId(string $playerId): array
    {
        return $this->createQueryBuilder('pt')
            ->where('pt.playerId = :playerId')
            ->setParameter('playerId', $playerId)
            ->orderBy('pt.discoveredAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByPlayerAndTechnology(string $playerId, string $technologyId): ?PlayerTechnologyEntity
    {
        return $this->createQueryBuilder('pt')
            ->where('pt.playerId = :playerId')
            ->andWhere('pt.technologyId = :technologyId')
            ->setParameter('playerId', $playerId)
            ->setParameter('technologyId', $technologyId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
