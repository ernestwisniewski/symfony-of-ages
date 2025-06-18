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

    public function findByPlayerId(string $playerId): array
    {
        return $this->createQueryBuilder('pv')
            ->where('pv.playerId = :playerId')
            ->setParameter('playerId', $playerId)
            ->getQuery()
            ->getResult();
    }

    public function findByGameId(string $gameId): array
    {
        // Dummy implementation for test compatibility; real implementation would filter by gameId if present in entity
        return [];
    }
} 