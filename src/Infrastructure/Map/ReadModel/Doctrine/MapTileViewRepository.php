<?php

namespace App\Infrastructure\Map\ReadModel\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MapTileViewEntity>
 */
class MapTileViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapTileViewEntity::class);
    }

    public function findByGameId(string $gameId): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.gameId = :gameId')
            ->setParameter('gameId', $gameId)
            ->getQuery()
            ->getResult();
    }
}
