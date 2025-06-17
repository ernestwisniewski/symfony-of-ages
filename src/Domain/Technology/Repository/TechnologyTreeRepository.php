<?php

namespace App\Domain\Technology\Repository;

use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Technology\TechnologyTree;
use Ecotone\Modelling\Attribute\Repository;

interface TechnologyTreeRepository
{
    #[Repository]
    public function findBy(PlayerId $playerId): ?TechnologyTree;

    #[Repository]
    public function save(TechnologyTree $technologyTree): void;
}
