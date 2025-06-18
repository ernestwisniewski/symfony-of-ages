<?php

namespace App\Infrastructure\Diplomacy\ReadModel\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DiplomacyViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiplomacyViewEntity::class);
    }

    public function findByPlayerId(string $playerId): array
    {
        $qb = $this->createQueryBuilder('d');
        $qb->where('d.initiatorId = :playerId OR d.targetId = :playerId')
            ->setParameter('playerId', $playerId);
        return $qb->getQuery()->getResult();
    }

    public function findByPlayerAndGame(string $playerId, string $gameId): array
    {
        return $this->findByPlayerId($playerId);
    }

    public function findActiveByGameId(string $gameId): array
    {
        return $this->findBy(['gameId' => $gameId, 'status' => 'accepted']);
    }

    public function findPendingByGameId(string $gameId): array
    {
        return $this->findBy(['gameId' => $gameId, 'status' => 'proposed']);
    }
}
