<?php

namespace App\Domain\Player\Repository;

use App\Domain\Player\Player;
use Ecotone\Modelling\Attribute\Repository;

interface PlayerRepository
{
    #[Repository]
    public function findBy(string $playerId): ?Player;
}
