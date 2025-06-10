<?php

namespace App\Infrastructure\Game\ReadModel\Doctrine;

use App\Infrastructure\Generic\Account\Doctrine\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameViewEntity>
 */
class GameViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameViewEntity::class);
    }

    /**
     * @return GameViewEntity[]
     */
    public function findByUser(?User $user): array
    {
        if (!$user) {
            return [];
        }

        return $this->createQueryBuilder('g')
            ->where('g.userId = :userId')
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getResult();
    }
}
