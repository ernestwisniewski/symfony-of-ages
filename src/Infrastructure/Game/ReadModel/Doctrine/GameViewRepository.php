<?php

namespace App\Infrastructure\Game\ReadModel\Doctrine;

use App\Infrastructure\Generic\Account\Doctrine\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GameViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameViewEntity::class);
    }

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
